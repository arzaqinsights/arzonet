<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Models\SubscriptionTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransferContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_contact_success()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $sourceList = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Source List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);
        $targetList = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Target List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        // Seed target list topics
        SubscriptionTopic::seedDefaultsFor($targetList->id, $user->id);
        $expectedTopicIds = SubscriptionTopic::where('email_list_id', $targetList->id)
            ->pluck('id')
            ->map('strval')
            ->toArray();

        // Create contact with sub-row sharing original_row_id
        $master = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $sourceList->id,
            'email' => 'master@example.com',
            'name' => 'John Doe',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'subscribed_topics' => ['1', '2'],
            'original_row_id' => 'group_123',
            'meta' => ['company' => 'SourceCompany'],
        ]);

        $sub = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $sourceList->id,
            'email' => 'sub@example.com',
            'name' => 'John Doe',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'subscribed_topics' => ['1', '2'],
            'original_row_id' => 'group_123',
            'meta' => ['company' => 'SourceCompany'],
        ]);

        $this->actingAs($user);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.transfer-contact', [
            'emailList' => $sourceList->id,
            'emailId' => $master->id
        ], false);

        $response = $this->post($url, [
            'target_list_id' => $targetList->id
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $master->refresh();
        $sub->refresh();

        // Both master and sub should be transferred to targetList
        $this->assertEquals($targetList->id, $master->email_list_id);
        $this->assertEquals($targetList->id, $sub->email_list_id);

        // Topics should be aligned to target list topics
        $this->assertEquals($expectedTopicIds, $master->subscribed_topics);
        $this->assertEquals($expectedTopicIds, $sub->subscribed_topics);

        // Custom fields meta should be cleared/reset to empty
        $this->assertEmpty($master->meta);
        $this->assertEmpty($sub->meta);
    }

    public function test_bulk_transfer_contact_success()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $sourceList = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Source List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);
        $targetList = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Target List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        // Seed target list topics
        SubscriptionTopic::seedDefaultsFor($targetList->id, $user->id);
        $expectedTopicIds = SubscriptionTopic::where('email_list_id', $targetList->id)
            ->pluck('id')
            ->map('strval')
            ->toArray();

        $master = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $sourceList->id,
            'email' => 'master@example.com',
            'name' => 'John Doe',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'subscribed_topics' => ['1', '2'],
            'original_row_id' => 'group_123',
            'meta' => ['company' => 'SourceCompany'],
        ]);

        $this->actingAs($user);

        // Dispatch bulk action via endpoint
        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.bulk-action', [
            'emailList' => $sourceList->id,
        ], false);

        $response = $this->post($url, [
            'action' => 'transfer',
            'ids' => [$master->id],
            'payload' => [
                'target_list_id' => $targetList->id
            ]
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Run the dispatched jobs
        $this->artisan('queue:work --once');

        $master->refresh();
        $this->assertEquals($targetList->id, $master->email_list_id);
        $this->assertEquals($expectedTopicIds, $master->subscribed_topics);
        $this->assertEmpty($master->meta);
    }
}

