<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Models\SubscriptionTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_subscribed_topics_to_empty_marks_contact_as_unsubscribed()
    {
        $user = User::factory()->create();
        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Test List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        SubscriptionTopic::seedDefaultsFor($list->id, $user->id);
        $topics = SubscriptionTopic::where('email_list_id', $list->id)->pluck('id')->toArray();

        $email = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'test@example.com',
            'subscription_status' => 'subscribed',
            'subscribed_topics' => $topics,
        ]);

        $this->assertEquals('subscribed', $email->subscription_status);

        // Update topics to empty array
        $email->update([
            'subscribed_topics' => [],
        ]);

        $this->assertEquals('unsubscribed', $email->subscription_status);
        $this->assertEmpty($email->subscribed_topics);
    }

    public function test_setting_status_to_unsubscribed_clears_subscribed_topics()
    {
        $user = User::factory()->create();
        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Test List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        SubscriptionTopic::seedDefaultsFor($list->id, $user->id);
        $topics = SubscriptionTopic::where('email_list_id', $list->id)->pluck('id')->toArray();

        $email = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'test@example.com',
            'subscription_status' => 'subscribed',
            'subscribed_topics' => $topics,
        ]);

        $this->assertEquals($topics, $email->subscribed_topics);

        // Update status to unsubscribed
        $email->update([
            'subscription_status' => 'unsubscribed',
        ]);

        $this->assertEmpty($email->subscribed_topics);
    }

    public function test_setting_status_to_subscribed_with_null_topics_seeds_default_list_topics()
    {
        $user = User::factory()->create();
        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Test List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        SubscriptionTopic::seedDefaultsFor($list->id, $user->id);
        $expectedTopics = SubscriptionTopic::where('email_list_id', $list->id)->pluck('id')->toArray();

        $email = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'test@example.com',
            'subscription_status' => 'subscribed',
            'subscribed_topics' => null,
        ]);

        $this->assertEquals('subscribed', $email->subscription_status);
        $this->assertEquals($expectedTopics, $email->subscribed_topics);
    }
}
