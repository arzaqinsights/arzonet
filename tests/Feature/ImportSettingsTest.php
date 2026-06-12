<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Models\SubscriptionTopic;
use App\Models\ActivityLog;
use App\Jobs\ProcessEmailListJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class ImportSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);
    }

    private function getAdminUrl(string $routeName, array $params = []): string
    {
        return 'http://admin.' . config('app.domain') . route($routeName, $params, false);
    }

    private function getAdminHeaders(): array
    {
        return [
            'Host' => 'admin.' . config('app.domain')
        ];
    }

    public function test_show_import_settings_page_returns_ok()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Import List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        SubscriptionTopic::seedDefaultsFor($list->id, $user->id);

        $this->actingAs($user);

        $url = $this->getAdminUrl('admin.email-lists.import-settings', ['emailList' => $list->id]);
        $response = $this->get($url, $this->getAdminHeaders());

        $response->assertStatus(200);
        $response->assertViewHas('topics');
        $response->assertViewHas('uniqueTags');
    }

    public function test_start_import_dispatches_job_with_correct_args()
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'admin']);
        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Import List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
            'original_filename' => 'contacts.csv',
        ]);

        SubscriptionTopic::seedDefaultsFor($list->id, $user->id);
        $topicIds = SubscriptionTopic::where('email_list_id', $list->id)->pluck('id')->toArray();

        $this->actingAs($user);

        $url = $this->getAdminUrl('admin.email-lists.start-import', ['emailList' => $list->id]);
        $response = $this->post($url, [
            'topics' => $topicIds,
            'tags' => ['VIP', 'Developer'],
            'new_tags' => 'Lead, Customer',
        ], $this->getAdminHeaders());

        $response->assertRedirect($this->getAdminUrl('admin.email-lists.show', ['emailList' => $list->id]));

        // Assert list status is processing
        $list->refresh();
        $this->assertEquals('processing', $list->status);

        // Assert activity log is created
        $log = ActivityLog::where('email_list_id', $list->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('started', $log->details['status']);
        $this->assertEquals(['VIP', 'Developer', 'Lead', 'Customer'], $log->details['tags']);

        // Assert job is dispatched
        Queue::assertPushed(ProcessEmailListJob::class, function ($job) use ($list, $log, $topicIds) {
            return $job->emailListId === $list->id &&
                $job->activityLogId === $log->id &&
                $job->selectedTags === ['VIP', 'Developer', 'Lead', 'Customer'] &&
                $job->selectedTopicIds === $topicIds;
        });
    }

    public function test_ajax_create_and_delete_topic()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Import List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        $this->actingAs($user);

        // 1. Create Topic via AJAX
        $createUrl = $this->getAdminUrl('admin.email-lists.ajax-create-topic', ['emailList' => $list->id]);
        $response = $this->post($createUrl, [
            'name' => 'New Newsletter',
            'description' => 'AJAX Description'
        ], $this->getAdminHeaders());

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $topicId = $response->json('topic.id');
        $this->assertDatabaseHas('subscription_topics', [
            'id' => $topicId,
            'name' => 'New Newsletter'
        ]);

        // 2. Delete Topic via AJAX
        $deleteUrl = $this->getAdminUrl('admin.email-lists.ajax-delete-topic', ['emailList' => $list->id, 'topic' => $topicId]);
        $deleteResponse = $this->post($deleteUrl, [], $this->getAdminHeaders());
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson(['success' => true]);

        $this->assertDatabaseMissing('subscription_topics', ['id' => $topicId]);
    }

    public function test_ajax_delete_tag()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Import List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        $contact = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'tagtest@example.com',
            'subscription_status' => 'subscribed',
            'tags' => ['VIP', 'Developer', 'Lead']
        ]);

        $this->actingAs($user);

        $url = $this->getAdminUrl('admin.email-lists.ajax-delete-tag', ['emailList' => $list->id]);
        $response = $this->post($url, [
            'tag' => 'Developer'
        ], $this->getAdminHeaders());

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $contact->refresh();
        $this->assertEquals(['VIP', 'Lead'], $contact->tags);
    }
}
