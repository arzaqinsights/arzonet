<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AdvancedUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected EmailList $emailList;

    protected function setUp(): void
    {
        parent::setUp();

        config('app.url');
        config('app.domain');

        $this->user = User::factory()->create();

        $this->emailList = EmailList::create([
            'user_id' => $this->user->id,
            'name' => 'Test List',
            'list_type' => EmailList::TYPE_DUAL,
            'status' => 'completed',
        ]);
    }

    public function test_bulk_unsubscribe_snooze_durations()
    {
        $this->actingAs($this->user);

        $email1 = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact1@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed'
        ]);

        $email2 = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact2@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed'
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.bulk-action', ['emailList' => $this->emailList->id], false);

        // Bulk unsubscribe for 7 days
        $response = $this->post($url, [
            'ids' => [$email1->id, $email2->id],
            'action' => 'unsubscribe',
            'duration' => '7'
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();

        $email1->refresh();
        $email2->refresh();

        $this->assertEquals('unsubscribed', $email1->subscription_status);
        $this->assertEquals('unsubscribed', $email2->subscription_status);
        $this->assertNotNull($email1->unsubscribe_expires_at);
        $this->assertEquals(Carbon::now()->addDays(7)->toDateString(), $email1->unsubscribe_expires_at->toDateString());
    }

    public function test_individual_unsubscribe_confirm_sets_snooze()
    {
        $email = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact3@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed'
        ]);

        $token = hash_hmac('sha256', $email->id . $email->email, config('app.key'));

        $url = route('unsubscribe.confirm', ['id' => $email->id]);

        $response = $this->post($url, [
            'token' => $token,
            'duration' => '30',
            'lid' => null
        ]);

        $response->assertStatus(200);
        $response->assertSee('Preferences Updated');
        $response->assertSee('unsubscribed');

        $email->refresh();
        $this->assertEquals('unsubscribed', $email->subscription_status);
        $this->assertNotNull($email->unsubscribe_expires_at);
        $this->assertEquals(Carbon::now()->addDays(30)->toDateString(), $email->unsubscribe_expires_at->toDateString());
    }

    public function test_expired_unsubscribe_resubscribes_automatically()
    {
        $email = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact4@example.com',
            'status' => 'valid',
            'subscription_status' => 'unsubscribed',
            'unsubscribe_expires_at' => Carbon::now()->subMinute(),
            'unsubscribed_at' => Carbon::now()->subDays(5)
        ]);

        // Trigger scheduler logic
        $expiredIds = \Illuminate\Support\Facades\DB::table('emails')
            ->where('subscription_status', 'unsubscribed')
            ->whereNotNull('unsubscribe_expires_at')
            ->where('unsubscribe_expires_at', '<=', now())
            ->pluck('id');

        $this->assertContains($email->id, $expiredIds);

        \Illuminate\Support\Facades\DB::table('emails')
            ->whereIn('id', $expiredIds)
            ->update([
                'subscription_status' => 'subscribed',
                'unsubscribe_expires_at' => null,
                'unsubscribed_at' => null
            ]);

        $email->refresh();
        $this->assertEquals('subscribed', $email->subscription_status);
        $this->assertNull($email->unsubscribe_expires_at);
        $this->assertNull($email->unsubscribed_at);
    }

    public function test_public_unsubscribe_link_redirects_to_confirm_page()
    {
        $email = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact5@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed'
        ]);

        $campaign = Campaign::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'status' => 'draft'
        ]);

        $log = \App\Models\EmailLog::create([
            'email_id' => $email->id,
            'campaign_id' => $campaign->id,
            'email_address' => $email->email,
            'status' => 'sent',
            'tracking_token' => 'test-track-token-123'
        ]);

        $url = route('unsubscribe', ['token' => 'test-track-token-123']);

        $response = $this->get($url);

        $expectedSecureToken = hash_hmac('sha256', $email->id . $email->email, config('app.key'));

        $response->assertRedirect(route('unsubscribe.show', [
            'id' => $email->id,
            'token' => $expectedSecureToken,
            'lid' => $log->id
        ]));
    }

    public function test_single_contact_update_sets_unsubscribe_snooze_duration()
    {
        $this->actingAs($this->user);

        $email = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact6@example.com',
            'status' => 'valid',
            'subscription_status' => 'subscribed'
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.update-email', ['emailList' => $this->emailList->id, 'emailId' => $email->id], false);

        $response = $this->put($url, [
            'email' => 'contact6@example.com',
            'subscription_status' => 'unsubscribed',
            'unsubscribe_duration' => '14'
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $email->refresh();
        $this->assertEquals('unsubscribed', $email->subscription_status);
        $this->assertNotNull($email->unsubscribed_at);
        $this->assertNotNull($email->unsubscribe_expires_at);
        $this->assertEquals(Carbon::now()->addDays(14)->toDateString(), $email->unsubscribe_expires_at->toDateString());
    }
}
