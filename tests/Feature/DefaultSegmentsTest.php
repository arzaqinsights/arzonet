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

    public function test_dynamic_segment_loading_on_list_view()
    {
        $this->actingAs($this->user);

        $john = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'john-dyn@example.com',
            'name' => 'John Dynamic',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Define segment for name contains 'Dynamic'
        $segment = Segment::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Dynamic Segment Test',
            'rules' => [
                ['field' => 'name', 'operator' => 'contains', 'value' => 'Dynamic']
            ]
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.filter', $this->emailList->id, false);

        $response = $this->postJson($url, [], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $data = $response->json();

        // Check if the dynamic segment badge name is rendered in html response
        $this->assertStringContainsString('Dynamic Segment Test', $data['html']);
    }

    public function test_replace_tags_bulk_action()
    {
        $john = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'john-tags@example.com',
            'name' => 'John Tags',
            'tags' => ['old-tag-1', 'old-tag-2'],
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        $activityLog = \App\Models\ActivityLog::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'type' => 'bulk_action',
            'details' => ['action' => 'replace_tags', 'status' => 'processing'],
        ]);

        $job = new \App\Jobs\AdvancedBulkActionJob(
            $this->emailList->id,
            'replace_tags',
            false,
            [],
            [$john->id],
            ['tags' => ['new-tag-1', 'new-tag-2']],
            $this->user->id,
            $activityLog->id
        );

        $job->handle();

        $john->refresh();
        $this->assertEquals(['new-tag-1', 'new-tag-2'], $john->tags);
    }

    public function test_add_and_remove_topics_bulk_action()
    {
        $john = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'john-topics@example.com',
            'name' => 'John Topics',
            'subscribed_topics' => ['1', '2'],
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        $activityLog = \App\Models\ActivityLog::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'type' => 'bulk_action',
            'details' => ['action' => 'add_topics', 'status' => 'processing'],
        ]);

        // Test Add Topics (adds '3' to '1', '2')
        $job = new \App\Jobs\AdvancedBulkActionJob(
            $this->emailList->id,
            'add_topics',
            false,
            [],
            [$john->id],
            ['topics' => ['3']],
            $this->user->id,
            $activityLog->id
        );

        $job->handle();

        $john->refresh();
        $this->assertEquals([1, 2, 3], $john->subscribed_topics);

        // Test Remove Topics (removes '2')
        $job2 = new \App\Jobs\AdvancedBulkActionJob(
            $this->emailList->id,
            'remove_topics',
            false,
            [],
            [$john->id],
            ['topics' => ['2']],
            $this->user->id,
            $activityLog->id
        );

        $job2->handle();

        $john->refresh();
        $this->assertEquals([1, 3], $john->subscribed_topics);
    }

    public function test_export_contacts_dispatches_job()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $this->actingAs($this->user);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.export', $this->emailList->id, false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect();
        
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ExportContactsJob::class);
    }

    public function test_download_export()
    {
        $this->actingAs($this->user);

        $filename = 'test_export_' . uniqid() . '.csv';
        $log = \App\Models\ActivityLog::create([
            'email_list_id' => $this->emailList->id,
            'user_id' => $this->user->id,
            'type' => 'export',
            'details' => [
                'filename' => $filename,
                'status' => 'completed'
            ]
        ]);

        $path = \Illuminate\Support\Facades\Storage::disk('local')->path('exports/' . $filename);
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, "ID,Name,Email\n1,John,john@example.com");

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.exports.download', $log->id, false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $this->assertEquals("ID,Name,Email\n1,John,john@example.com", $response->streamedContent());

        // Cleanup
        unlink($path);
    }

    public function test_check_status_includes_export_info()
    {
        $this->actingAs($this->user);

        // 1. Assert initial state is null
        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.status', $this->emailList->id, false);
        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);
        $response->assertJsonFragment([
            'active_export' => null,
            'last_export_completed' => null
        ]);

        // 2. Create started export activity log and assert active_export is populated
        $activeLog = \App\Models\ActivityLog::create([
            'email_list_id' => $this->emailList->id,
            'user_id' => $this->user->id,
            'type' => 'export',
            'details' => [
                'filename' => 'active_export.xlsx',
                'status' => 'started'
            ]
        ]);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);
        $response->assertJsonPath('active_export.id', $activeLog->id);
        $response->assertJsonPath('active_export.filename', 'active_export.xlsx');
        $response->assertJsonPath('active_export.status', 'started');

        // 3. Create completed export activity log and assert last_export_completed is populated
        $completedLog = \App\Models\ActivityLog::create([
            'email_list_id' => $this->emailList->id,
            'user_id' => $this->user->id,
            'type' => 'export',
            'details' => [
                'filename' => 'completed_export.xlsx',
                'status' => 'completed'
            ]
        ]);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);
        $response->assertJsonPath('last_export_completed.id', $completedLog->id);
        $response->assertJsonPath('last_export_completed.filename', 'completed_export.xlsx');
        $response->assertJsonPath('last_export_completed.status', 'completed');
    }

    public function test_contact_timeline_activities()
    {
        $contact = \App\Models\Email::create([
            'email_list_id' => $this->emailList->id,
            'user_id' => $this->user->id,
            'email' => 'timeline_test@example.com',
            'name' => 'Timeline Tester',
            'tags' => ['old-tag'],
        ]);

        // 1. Tag addition and removal
        $contact->update(['tags' => ['old-tag', 'new-tag']]);
        $this->assertTrue($contact->activities()->where('type', 'tag_added')->exists());

        $contact->update(['tags' => ['new-tag']]);
        $this->assertTrue($contact->activities()->where('type', 'tag_removed')->exists());

        // 2. Note addition
        $note = \App\Models\ContactNote::create([
            'email_id' => $contact->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test note content.',
        ]);
        $this->assertTrue($contact->activities()->where('type', 'note_added')->exists());

        // 3. Task created and completed
        $task = \App\Models\ContactTask::create([
            'email_id' => $contact->id,
            'user_id' => $this->user->id,
            'title' => 'Test Task',
        ]);
        $this->assertTrue($contact->activities()->where('type', 'task_created')->exists());

        $task->update(['is_completed' => true]);
        $this->assertTrue($contact->activities()->where('type', 'task_completed')->exists());

        // 4. Deal stage changed
        $pipeline = \App\Models\Pipeline::create([
            'email_list_id' => $this->emailList->id,
            'user_id' => $this->user->id,
            'name' => 'Test Pipeline',
        ]);
        $stage1 = \App\Models\PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'user_id' => $this->user->id,
            'name' => 'Stage 1',
        ]);
        $stage2 = \App\Models\PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'user_id' => $this->user->id,
            'name' => 'Stage 2',
        ]);
        $deal = \App\Models\Deal::create([
            'email_id' => $contact->id,
            'pipeline_stage_id' => $stage1->id,
            'title' => 'Timeline Test Deal',
            'user_id' => $this->user->id,
        ]);

        $deal->update(['pipeline_stage_id' => $stage2->id]);
        $this->assertTrue($contact->activities()->where('type', 'stage_changed')->exists());

        // 5. Sequence enrolled
        $sequence = \App\Models\Sequence::create([
            'email_list_id' => $this->emailList->id,
            'user_id' => $this->user->id,
            'name' => 'Test Sequence',
        ]);
        $enrollment = \App\Models\SequenceEnrollment::create([
            'sequence_id' => $sequence->id,
            'email_id' => $contact->id,
            'current_step_number' => 1,
            'status' => 'active',
        ]);
        $this->assertTrue($contact->activities()->where('type', 'sequence_enrolled')->exists());

        $enrollment->update(['status' => 'completed']);
        $this->assertTrue($contact->activities()->where('type', 'sequence_completed')->exists());
    }

    public function test_contact_profile_campaign_history()
    {
        $this->actingAs($this->user);

        $contact = \App\Models\Email::create([
            'email_list_id' => $this->emailList->id,
            'user_id' => $this->user->id,
            'email' => 'campaign_hist@example.com',
            'name' => 'Campaign History Tester',
        ]);

        $campaign = \App\Models\Campaign::create([
            'email_list_id' => $this->emailList->id,
            'user_id' => $this->user->id,
            'name' => 'History Test Campaign',
            'subject' => 'Hello',
            'content' => 'Content',
            'status' => 'completed',
        ]);

        $log = \App\Models\EmailLog::create([
            'user_id' => $this->user->id,
            'email_id' => $contact->id,
            'campaign_id' => $campaign->id,
            'email_address' => $contact->email,
            'status' => 'delivered',
            'sent_at' => now(),
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.contact.profile', [$this->emailList->id, $contact->id], false);

        $response = $this->getJson($url, [
            'Host' => 'admin.' . config('app.domain' )
        ]);

        $response->assertOk();
        $response->assertJsonPath('logs.0.campaign.name', 'History Test Campaign');
        $response->assertJsonPath('logs.0.status', 'delivered');
    }
}
