<?php

namespace App\Jobs;

use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\MetaApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class ProcessWhatsAppCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function handle(MetaApiService $metaApi)
    {
        $campaign = WhatsAppCampaign::with(['template.whatsappAccount'])->find($this->campaignId);
        if (!$campaign || $campaign->status !== 'processing') return;

        $template = $campaign->template;
        $account = $template->whatsappAccount;
        
        // Get audience (assuming we use Email model as Contacts)
        // In a real app, you'd filter by campaign's audience settings
        $contacts = \App\Models\Email::where('user_id', $campaign->user_id)
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_opt_in', true)
            ->get();

        $campaign->update(['total_recipients' => $contacts->count()]);

        foreach ($contacts as $contact) {
            try {
                $accessToken = Crypt::decryptString($account->access_token);

                $response = $metaApi->sendTemplateMessage(
                    $account->phone_number_id,
                    $accessToken,
                    $contact->whatsapp_number,
                    $template->name,
                    $template->language
                );

                $waId = $response['messages'][0]['id'] ?? null;

                if ($waId) {
                    WhatsAppMessage::create([
                        'user_id' => $campaign->user_id,
                        'whatsapp_account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'wa_message_id' => $waId,
                        'direction' => 'outbound',
                        'type' => 'template',
                        'message_body' => $template->body,
                        'status' => 'sent',
                    ]);
                    $campaign->increment('sent_count');
                } else {
                    $campaign->increment('failed_count');
                }
            } catch (\Exception $e) {
                Log::error("WhatsApp Campaign Error: " . $e->getMessage());
                $campaign->increment('failed_count');
            }

            // Simple rate limiting (Meta has its own, but good to be careful)
            usleep(100000); // 0.1s
        }

        $campaign->update(['status' => 'completed']);
    }
}
