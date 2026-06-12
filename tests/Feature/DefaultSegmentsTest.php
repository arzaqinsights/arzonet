<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Models\Segment;
use App\Models\Campaign;
use App\Models\EmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DefaultSegmentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected EmailList $emailList;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->emailList = EmailList::create([
            'user_id' => $this->user->id,
            'name' => 'Test List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);
    }

    public function test_default_segments_are_seeded_on_list_creation()
    {
        // When a list is created, defaults are seeded automatically by the model boot event.
        // Let's verify they exist for the list we created in setUp().
        $expectedNames = [
            'Recent Openers',
            'Recent Clickers',
            'Sent in Last Campaign',
            'Recently Sent (7 Days)',
            'Opened Any Email',
            'Clicked Any Link',
            'Without Name',
            'Unsubscribed',
            'Valid Contacts',
            'Invalid Contacts',
        ];

        foreach ($expectedNames as $name) {
            $this->assertDatabaseHas('segments', [
                'email_list_id' => $this->emailList->id,
                'name' => $name,
            ]);
        }
    }

    public function test_opened_and_clicked_rules_evaluation()
    {
        // 1. Create contacts
        $john = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        $bob = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'bob@example.com',
            'name' => 'Bob Marley',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        $alice = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'alice@example.com',
            'name' => 'Alice Cooper',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Create log: John opened, Bob clicked and opened, Alice neither.
        EmailLog::create([
            'user_id' => $this->user->id,
            'email_id' => $john->id,
            'email_address' => $john->email,
            'open_count' => 1,
            'click_count' => 0,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        EmailLog::create([
            'user_id' => $this->user->id,
            'email_id' => $bob->id,
            'email_address' => $bob->email,
            'open_count' => 2,
            'click_count' => 1,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Define segment for opened_email = 1
        $segmentOpened = Segment::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Test Opened',
            'rules' => [
                ['field' => 'opened_email', 'operator' => 'equals', 'value' => '1']
            ]
        ]);

        // Define segment for clicked_email = 1
        $segmentClicked = Segment::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Test Clicked',
            'rules' => [
                ['field' => 'clicked_email', 'operator' => 'equals', 'value' => '1']
            ]
        ]);

        // In-memory evaluation matchesContact
        $this->assertTrue($segmentOpened->matchesContact($john));
        $this->assertTrue($segmentOpened->matchesContact($bob));
        $this->assertFalse($segmentOpened->matchesContact($alice));

        $this->assertFalse($segmentClicked->matchesContact($john));
        $this->assertTrue($segmentClicked->matchesContact($bob));
        $this->assertFalse($segmentClicked->matchesContact($alice));

        // DB Query evaluation applyRulesToQuery
        $openedQuery = Segment::applyRulesToQuery(Email::query(), $segmentOpened->rules);
        $openedContacts = $openedQuery->pluck('id')->toArray();
        $this->assertContains($john->id, $openedContacts);
        $this->assertContains($bob->id, $openedContacts);
        $this->assertNotContains($alice->id, $openedContacts);

        $clickedQuery = Segment::applyRulesToQuery(Email::query(), $segmentClicked->rules);
        $clickedContacts = $clickedQuery->pluck('id')->toArray();
        $this->assertNotContains($john->id, $clickedContacts);
        $this->assertContains($bob->id, $clickedContacts);
        $this->assertNotContains($alice->id, $clickedContacts);
    }

    public function test_sent_in_last_campaign_rules_evaluation()
    {
        $john = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'john@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        $bob = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'bob@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Create two campaigns: Campaign A (older completed), Campaign B (newer completed)
        $campaignA = Campaign::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Campaign A',
            'status' => 'completed',
            'completed_at' => now()->subDay(),
        ]);

        $campaignB = Campaign::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Campaign B',
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Logs: John gets Campaign A and Campaign B. Bob only gets Campaign A.
        EmailLog::create([
            'user_id' => $this->user->id,
            'email_id' => $john->id,
            'campaign_id' => $campaignA->id,
            'email_address' => $john->email,
            'status' => 'sent',
        ]);

        EmailLog::create([
            'user_id' => $this->user->id,
            'email_id' => $bob->id,
            'campaign_id' => $campaignA->id,
            'email_address' => $bob->email,
            'status' => 'sent',
        ]);

        EmailLog::create([
            'user_id' => $this->user->id,
            'email_id' => $john->id,
            'campaign_id' => $campaignB->id,
            'email_address' => $john->email,
            'status' => 'sent',
        ]);

        $segmentLastCampaign = Segment::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Sent Last Campaign',
            'rules' => [
                ['field' => 'sent_in_last_campaign', 'operator' => 'equals', 'value' => '1']
            ]
        ]);

        // In-memory matchesContact (Campaign B is the latest completed for the list)
        $this->assertTrue($segmentLastCampaign->matchesContact($john));
        $this->assertFalse($segmentLastCampaign->matchesContact($bob));

        // DB Query evaluation applyRulesToQuery
        $query = Segment::applyRulesToQuery(Email::query(), $segmentLastCampaign->rules);
        $results = $query->pluck('id')->toArray();
        $this->assertContains($john->id, $results);
        $this->assertNotContains($bob->id, $results);
    }
}
