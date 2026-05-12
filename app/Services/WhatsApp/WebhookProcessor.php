<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppConversation;
use App\Models\Email as Contact;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WebhookProcessor
{
    public function process(array $payload)
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                
                // Handle Messages
                if (isset($value['messages'])) {
                    $this->handleMessages($value);
                }

                // Handle Status Updates
                if (isset($value['statuses'])) {
                    $this->handleStatuses($value);
                }

                // Handle Template Status Changes
                if ($change['field'] === 'message_template_status_update') {
                    $this->handleTemplateStatusUpdate($value);
                }
            }
        }
    }

    protected function handleTemplateStatusUpdate(array $value)
    {
        $templateId = $value['message_template_id'] ?? null;
        $status = $value['event'] ?? null;

        if ($templateId && $status) {
            \App\Models\WhatsAppTemplate::where('meta_template_id', $templateId)->update([
                'status' => strtolower($status)
            ]);
        }
    }

    protected function handleMessages(array $value)
    {
        $metadata = $value['metadata'] ?? [];
        $phoneNumberId = $metadata['phone_number_id'] ?? null;
        
        $account = WhatsAppAccount::where('phone_number_id', $phoneNumberId)->first();
        if (!$account) return;

        foreach ($value['messages'] as $waMsg) {
            $from = $waMsg['from']; // User's phone number
            $waId = $waMsg['id'];
            $type = $waMsg['type'];
            $body = $waMsg['text']['body'] ?? '';

            // Find or create contact
            $contact = Contact::where('whatsapp_number', $from)
                ->orWhere('email', $from . '@whatsapp') // Fallback identifier
                ->first();

            if (!$contact) {
                $contact = Contact::create([
                    'user_id' => $account->user_id,
                    'whatsapp_number' => $from,
                    'name' => $value['contacts'][0]['profile']['name'] ?? $from,
                    'email' => $from . '@whatsapp',
                    'subscription_status' => 'subscribed',
                ]);
            }

            DB::transaction(function () use ($account, $contact, $waId, $type, $body) {
                // Save Message
                WhatsAppMessage::create([
                    'user_id' => $account->user_id,
                    'whatsapp_account_id' => $account->id,
                    'contact_id' => $contact->id,
                    'wa_message_id' => $waId,
                    'direction' => 'inbound',
                    'type' => $type,
                    'message_body' => $body,
                    'status' => 'received',
                ]);

                // Update/Create Conversation
                WhatsAppConversation::updateOrCreate(
                    [
                        'whatsapp_account_id' => $account->id,
                        'contact_id' => $contact->id,
                    ],
                    [
                        'user_id' => $account->user_id,
                        'last_message_at' => now(),
                        'unread_count' => DB::raw('unread_count + 1'),
                    ]
                );

                // Update Contact
                $contact->update(['whatsapp_last_message_at' => now()]);
            });
        }
    }

    protected function handleStatuses(array $value)
    {
        foreach ($value['statuses'] as $status) {
            $waId = $status['id'];
            $newStatus = $status['status'];

            WhatsAppMessage::where('wa_message_id', $waId)->update([
                'status' => $newStatus,
                'payload' => DB::raw("JSON_MERGE_PATCH(COALESCE(payload, '{}'), '" . json_encode(['status_history' => [$status]]) . "')")
            ]);
        }
    }
}
