<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Models\SubscriptionTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContactFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_by_unsubscribed_status()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Filtering List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        // Seed default topics
        SubscriptionTopic::seedDefaultsFor($list->id, $user->id);

        // Create subscribed contact
        $subscribed = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'active-sub@example.com',
            'name' => 'Subscribed Contact',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Create unsubscribed contact
        $unsubscribed = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'opt-out@example.com',
            'name' => 'Unsubscribed Contact',
            'status' => 'valid',
            'subscription_status' => 'unsubscribed',
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.filter', $list->id, false);
        
        $response = $this->postJson($url, [
            'subscription' => ['unsubscribed']
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $data = $response->json();
        
        $this->assertStringContainsString('opt-out@example.com', $data['html']);
        $this->assertStringNotContainsString('active-sub@example.com', $data['html']);
    }

    public function test_filter_by_channel_type()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Channel Filtering List',
            'list_type' => EmailList::TYPE_DUAL,
            'status' => 'completed',
        ]);

        // Contact with email only
        $emailOnly = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'emailonly@example.com',
            'name' => 'Email Only',
            'status' => 'valid',
        ]);

        // Contact with whatsapp only
        $whatsappOnly = Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'whatsapp_number' => '919876543210',
            'name' => 'WhatsApp Only',
            'status' => 'valid',
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.filter', $list->id, false);
        
        // Filter by only_whatsapp
        $response = $this->postJson($url, [
            'channel' => ['only_whatsapp']
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $data = $response->json();
        
        $this->assertStringContainsString('919876543210', $data['html']);
        $this->assertStringNotContainsString('emailonly@example.com', $data['html']);
    }

    public function test_filter_by_signup_source()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Source Filtering List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        // Contact from Website
        Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'website@example.com',
            'name' => 'Website Optin',
            'signup_source' => 'Website Widget',
            'status' => 'valid',
        ]);

        // Contact from CSV
        Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'csvimport@example.com',
            'name' => 'CSV Import',
            'signup_source' => 'CSV Upload',
            'status' => 'valid',
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.filter', $list->id, false);

        $response = $this->postJson($url, [
            'source' => ['Website Widget']
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertStringContainsString('website@example.com', $data['html']);
        $this->assertStringNotContainsString('csvimport@example.com', $data['html']);
    }

    public function test_filter_by_bounce_count_rule()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Bounce Count List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        // Contact with bounce count
        Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'highbounce@example.com',
            'name' => 'High Bounce',
            'bounce_count' => 5,
            'status' => 'valid',
        ]);

        // Contact with no bounces
        Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'nobounce@example.com',
            'name' => 'No Bounce',
            'bounce_count' => 0,
            'status' => 'valid',
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.filter', $list->id, false);

        $response = $this->postJson($url, [
            'advanced_rules' => [
                [
                    'field' => 'bounce_count',
                    'operator' => 'greater_than',
                    'value' => '2'
                ]
            ]
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertStringContainsString('highbounce@example.com', $data['html']);
        $this->assertStringNotContainsString('nobounce@example.com', $data['html']);
    }

    public function test_filter_by_tags()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Tag List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'tagged-vip@example.com',
            'name' => 'VIP Contact',
            'tags' => ['VIP', 'Lead'],
            'status' => 'valid',
        ]);

        Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'tagged-regular@example.com',
            'name' => 'Regular Contact',
            'tags' => ['Regular'],
            'status' => 'valid',
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.filter', $list->id, false);

        $response = $this->postJson($url, [
            'tag' => ['VIP']
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertStringContainsString('tagged-vip@example.com', $data['html']);
        $this->assertStringNotContainsString('tagged-regular@example.com', $data['html']);
    }

    public function test_filter_by_topics_as_integers_and_strings()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $list = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Topic List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        // Contact with integer topic ID in json array
        Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'int-topic@example.com',
            'name' => 'Int Topic',
            'subscribed_topics' => [1],
            'status' => 'valid',
        ]);

        // Contact with string topic ID in json array
        Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'string-topic@example.com',
            'name' => 'String Topic',
            'subscribed_topics' => ['1'],
            'status' => 'valid',
        ]);

        // Contact with different topic ID
        Email::create([
            'user_id' => $user->id,
            'email_list_id' => $list->id,
            'email' => 'other-topic@example.com',
            'name' => 'Other Topic',
            'subscribed_topics' => [99],
            'status' => 'valid',
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.filter', $list->id, false);

        // Filter by topic 1 (front-end sends strings)
        $response = $this->postJson($url, [
            'topic' => ['1']
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertStringContainsString('int-topic@example.com', $data['html']);
        $this->assertStringContainsString('string-topic@example.com', $data['html']);
        $this->assertStringNotContainsString('other-topic@example.com', $data['html']);
    }
}
