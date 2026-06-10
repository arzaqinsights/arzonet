<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppAccount;
use App\Models\ContactActivity;
use App\Models\Deal;
use App\Jobs\CalculateLeadScoreJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AutomatedLeadScoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected EmailList $emailList;
    protected WhatsAppAccount $whatsappAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->emailList = EmailList::create([
            'user_id' => $this->user->id,
            'name' => 'Lead Score Test List',
            'list_type' => EmailList::TYPE_DUAL,
            'status' => 'completed',
        ]);

        $this->whatsappAccount = WhatsAppAccount::create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Business',
            'display_name' => 'Test WA Display',
            'phone_number' => '+1234567890',
            'phone_number_id' => 'wa_phone_id_123',
            'whatsapp_business_account_id' => 'biz_acc_id_123',
            'access_token' => 'access_token_mock',
            'status' => 'active',
        ]);
    }

    public function test_default_lead_scores_on_contact_creation()
    {
        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'newlead@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        $contact->refresh();
        // Base Email Score: 1 + Subscribed: +1 => 2
        // Base WA Score: 1 + Subscribed (by default is 'subscribed'): +1 => 2
        // Overall: (2 + 2) * 5 = 20
        $this->assertEquals(2, $contact->email_lead_score);
        $this->assertEquals(2, $contact->whatsapp_lead_score);
        $this->assertEquals(20, $contact->engagement_score);
    }

    public function test_email_lead_score_calculations()
    {
        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'emailtest@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Add 3 opens (+2)
        ContactActivity::create([
            'email_id' => $contact->id,
            'type' => 'opened',
        ]);
        ContactActivity::create([
            'email_id' => $contact->id,
            'type' => 'opened',
        ]);
        ContactActivity::create([
            'email_id' => $contact->id,
            'type' => 'opened',
        ]);

        // Add 1 click (+2)
        ContactActivity::create([
            'email_id' => $contact->id,
            'type' => 'clicked',
        ]);

        // Add active deal (+2)
        $pipeline = \App\Models\Pipeline::create([
            'user_id' => $this->user->id,
            'name' => 'Sales Pipeline',
        ]);
        $stage = \App\Models\PipelineStage::create([
            'user_id' => $this->user->id,
            'pipeline_id' => $pipeline->id,
            'name' => 'Lead',
            'color' => '#3b82f6',
            'order' => 1,
        ]);
        Deal::create([
            'user_id' => $this->user->id,
            'email_id' => $contact->id,
            'pipeline_stage_id' => $stage->id,
            'title' => 'Test Deal',
            'status' => 'open',
        ]);

        // Set last_engaged_at within 7 days (+2)
        $contact->update([
            'last_engaged_at' => now()->subDays(2),
        ]);

        // Dispatch job manually to execute calculation with the active relationships
        CalculateLeadScoreJob::dispatch($contact->id);

        $contact->refresh();
        // Base: 1
        // Subscribed: +1
        // Opens >= 3: +2
        // Clicks >= 1: +2
        // Active deals: +2
        // Recency <= 7 days: +2
        // Total = 1 + 1 + 2 + 2 + 2 + 2 = 10
        $this->assertEquals(10, $contact->email_lead_score);
        $this->assertEquals(2, $contact->whatsapp_lead_score); // Default subscribed is 2
        $this->assertEquals(60, $contact->engagement_score); // (10 + 2) * 5 = 60
    }

    public function test_email_lead_score_penalties()
    {
        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'penaltytest@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Add 6 opens (+3)
        for ($i = 0; $i < 6; $i++) {
            ContactActivity::create([
                'email_id' => $contact->id,
                'type' => 'opened',
            ]);
        }

        // Base 1 + 1 (subscribed) + 3 (opens) = 5.
        // soft_bounce penalty (-5)
        $contact->update([
            'email_status' => 'soft_bounce',
        ]);

        $contact->refresh();
        // Base 5 - 5 = 0, clamped to min 1
        $this->assertEquals(1, $contact->email_lead_score);
    }

    public function test_whatsapp_lead_score_calculations()
    {
        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'wagoal@example.com',
            'whatsapp_number' => '+1234567890',
            'whatsapp_subscription_status' => 'subscribed',
        ]);

        // Add WhatsApp Messages
        // Inbound: 2 replies (+4)
        WhatsAppMessage::create([
            'user_id' => $this->user->id,
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'contact_id' => $contact->id,
            'direction' => 'inbound',
            'message_body' => 'Yes, please!',
            'wa_message_id' => 'msg_1',
        ]);
        WhatsAppMessage::create([
            'user_id' => $this->user->id,
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'contact_id' => $contact->id,
            'direction' => 'inbound',
            'message_body' => 'Tell me more',
            'wa_message_id' => 'msg_2',
        ]);

        // Outbound: 1 message sent (+1)
        WhatsAppMessage::create([
            'user_id' => $this->user->id,
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'contact_id' => $contact->id,
            'direction' => 'outbound',
            'message_body' => 'Hello there',
            'wa_message_id' => 'msg_3',
        ]);

        // Set whatsapp_last_message_at within 7 days (+2)
        $contact->update([
            'whatsapp_last_message_at' => now()->subDays(1),
        ]);

        CalculateLeadScoreJob::dispatch($contact->id);

        $contact->refresh();
        // Base: 1
        // Subscribed: +1
        // Inbound (2): +4
        // Outbound (1): +1
        // Recency <= 7 days: +2
        // Total = 1 + 1 + 4 + 1 + 2 = 9
        $this->assertEquals(9, $contact->whatsapp_lead_score);
        $this->assertEquals(2, $contact->email_lead_score); // Default 1 + 1 (subscribed)
        $this->assertEquals(55, $contact->engagement_score); // (2 + 9) * 5 = 55
    }

    public function test_whatsapp_lead_score_unsubscribed_penalty()
    {
        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'waunsub@example.com',
            'whatsapp_number' => '+1234567890',
            'whatsapp_subscription_status' => 'unsubscribed',
        ]);

        $contact->refresh();
        // Base 1 - 5 = -4, clamped to 1
        $this->assertEquals(1, $contact->whatsapp_lead_score);
    }

    public function test_whatsapp_message_created_triggers_lead_score_calculation()
    {
        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'watrigger@example.com',
            'whatsapp_number' => '+1999999999',
            'whatsapp_subscription_status' => 'subscribed',
        ]);

        $contact->refresh();
        // Should default to 2 because subscription status is 'subscribed'
        $this->assertEquals(2, $contact->whatsapp_lead_score);

        // Create an inbound whatsapp message to trigger the lead scoring recalculation
        WhatsAppMessage::create([
            'user_id' => $this->user->id,
            'whatsapp_account_id' => $this->whatsappAccount->id,
            'contact_id' => $contact->id,
            'direction' => 'inbound',
            'message_body' => 'Hook me up!',
            'wa_message_id' => 'msg_trigger_1',
        ]);

        $contact->refresh();
        // Subscribed (+1) + Inbound message (+2) + Base (1) = 4
        $this->assertEquals(4, $contact->whatsapp_lead_score);
    }
}
