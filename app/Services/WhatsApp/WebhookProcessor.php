<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppTemplate;
use App\Models\Email as Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookProcessor
{
    /**
     * Whitelist of allowed fields in webhook entry changes.
     */
    protected array $allowedFields = [
        'messages',
        'statuses',
        'message_template_status_update',
        'message_template_quality_update',
        'phone_number_name_update',
        'phone_number_quality_update',
        'account_update'
    ];

    public function process(array $payload)
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $field = $change['field'] ?? null;
                $value = $change['value'] ?? [];

                if (!in_array($field, $this->allowedFields)) {
                    continue;
                }

                try {
                    match ($field) {
                        'messages' => $this->handleMessages($value),
                        'statuses' => $this->handleStatuses($value),
                        'message_template_status_update' => $this->handleTemplateStatusUpdate($value),
                        'message_template_quality_update' => $this->handleTemplateQualityUpdate($value),
                        'phone_number_quality_update' => $this->handlePhoneNumberQualityUpdate($value),
                        'account_update' => $this->handleAccountUpdate($value),
                        default => null,
                    };
                } catch (\Exception $e) {
                    Log::error("Webhook Field Processing Error [{$field}]: " . $e->getMessage(), ['payload' => $value]);
                }
            }
        }
    }

    /**
     * Handle Incoming Messages (Text, Image, Document, Audio)
     */
    protected function handleMessages(array $value)
    {
        $metadata = $value['metadata'] ?? [];
        $phoneNumberId = $metadata['phone_number_id'] ?? null;
        
        $account = WhatsAppAccount::where('phone_number_id', $phoneNumberId)->first();
        if (!$account) return;

        $contactsData = $value['contacts'] ?? [];

        foreach ($value['messages'] as $index => $waMsg) {
            $from = $waMsg['from']; 
            $waId = $waMsg['id'];
            $type = $waMsg['type'];
            
            // Extract body based on type
            $body = '';
            $mediaMetadata = [];

            if ($type === 'text') {
                $body = $waMsg['text']['body'] ?? '';
            } elseif (in_array($type, ['image', 'document', 'audio', 'video', 'voice'])) {
                $media = $waMsg[$type] ?? [];
                $body = $media['caption'] ?? ucfirst($type);
                $mediaMetadata = [
                    'id' => $media['id'] ?? null,
                    'mime_type' => $media['mime_type'] ?? null,
                    'sha256' => $media['sha256'] ?? null,
                    'filename' => $media['filename'] ?? null,
                ];
            }

            // Find or create contact
            $contact = Contact::where('whatsapp_number', $from)
                ->where('user_id', $account->user_id)
                ->first();

            if (!$contact) {
                $profileName = $contactsData[$index]['profile']['name'] ?? $from;
                $contact = Contact::create([
                    'user_id' => $account->user_id,
                    'whatsapp_number' => $from,
                    'name' => $profileName,
                    'email' => $from . '@whatsapp.com', // Dummy for multi-channel identity
                    'subscription_status' => 'subscribed',
                ]);
            }

            DB::transaction(function () use ($account, $contact, $waId, $type, $body, $mediaMetadata) {
                // 1. Create Message
                $message = WhatsAppMessage::create([
                    'user_id' => $account->user_id,
                    'whatsapp_account_id' => $account->id,
                    'contact_id' => $contact->id,
                    'wa_message_id' => $waId,
                    'direction' => 'inbound',
                    'type' => $type,
                    'message_body' => $body,
                    'status' => 'received',
                    'metadata' => $mediaMetadata,
                ]);

                // 2. Update/Create Conversation
                WhatsAppConversation::updateOrCreate(
                    [
                        'whatsapp_account_id' => $account->id,
                        'contact_id' => $contact->id,
                    ],
                    [
                        'user_id' => $account->user_id,
                        'last_message_at' => now(),
                        'last_message_preview' => \Illuminate\Support\Str::limit($body, 100),
                        'unread_count' => DB::raw('unread_count + 1'),
                    ]
                );

                // 3. Update Contact Timestamp & Check for Opt-out Keywords
                $updateData = ['whatsapp_last_message_at' => now()];

                $optOutKeywords = ['STOP', 'UNSUBSCRIBE', 'REMOVE', 'CANCEL'];
                $cleanBody = strtoupper(trim($body));
                
                if (in_array($cleanBody, $optOutKeywords)) {
                    $updateData['whatsapp_opt_in'] = false;
                    $updateData['whatsapp_subscription_status'] = 'unsubscribed';
                    $updateData['whatsapp_unsubscribed_at'] = now();
                    
                    Log::info("WhatsApp Opt-out Triggered: Contact #{$contact->id} ({$contact->whatsapp_number}) unsubscribed via keyword '{$cleanBody}'");
                }

                $contact->update($updateData);
            });
        }
    }

    /**
     * Handle Delivery Status Updates (Sent, Delivered, Read, Failed)
     */
    protected function handleStatuses(array $value)
    {
        foreach ($value['statuses'] as $status) {
            $waId = $status['id'];
            $newStatus = $status['status'];
            $timestamp = isset($status['timestamp']) ? Carbon::createFromTimestamp($status['timestamp']) : now();

            $message = WhatsAppMessage::where('wa_message_id', $waId)->first();
            if (!$message) continue;

            DB::transaction(function () use ($message, $newStatus, $timestamp, $status) {
                // Update Message Status
                $message->update(['status' => $newStatus]);

                // Log Status Change
                DB::table('whatsapp_message_statuses')->insert([
                    'whatsapp_message_id' => $message->id,
                    'status' => $newStatus,
                    'occurred_at' => $timestamp,
                    'raw_response' => json_encode($status),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // If failed, log the error code
                if ($newStatus === 'failed' && isset($status['errors'])) {
                    $errors = $status['errors'];
                    $message->update(['payload' => array_merge((array)$message->payload, ['errors' => $errors])]);
                }
            });
        }
    }

    /**
     * Handle Template Status Changes
     */
    protected function handleTemplateStatusUpdate(array $value)
    {
        $templateId = $value['message_template_id'] ?? null;
        $status = $value['event'] ?? null;

        if ($templateId && $status) {
            WhatsAppTemplate::where('meta_template_id', $templateId)->update([
                'status' => strtolower($status)
            ]);
        }
    }

    /**
     * Handle Template Quality Rating Changes
     */
    protected function handleTemplateQualityUpdate(array $value)
    {
        $templateId = $value['message_template_id'] ?? null;
        $quality = $value['new_quality_score'] ?? null;

        if ($templateId && $quality) {
            WhatsAppTemplate::where('meta_template_id', $templateId)->update([
                'metadata' => DB::raw("JSON_MERGE_PATCH(COALESCE(metadata, '{}'), '{\"quality_score\": \"$quality\"}')")
            ]);
        }
    }

    /**
     * Handle Phone Number Quality & Messaging Limit Changes
     */
    protected function handlePhoneNumberQualityUpdate(array $value)
    {
        $displayPhoneNumber = $value['display_phone_number'] ?? null;
        $qualityRating = $value['current_limit'] ?? null; // Meta sometimes puts rating here or in event

        $account = WhatsAppAccount::where('phone_number', $displayPhoneNumber)->first();
        if ($account) {
            $account->update([
                'quality_rating' => $value['new_quality_rating'] ?? $account->quality_rating,
                'messaging_limit_tier' => $value['messaging_limit_tier'] ?? $account->messaging_limit_tier,
            ]);
        }
    }

    /**
     * Handle Account-level updates
     */
    protected function handleAccountUpdate(array $value)
    {
        // Handle WABA status changes (e.g. disabled, verified)
        Log::info("WhatsApp Account Update Event: ", $value);
    }
}
