<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Models\BlacklistedEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BlacklistManagementTest extends TestCase
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

    public function test_single_add_to_blacklist_marks_existing_contacts_blocked()
    {
        $this->actingAs($this->user);

        // 1. Create a contact with status valid
        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'spam-target@example.com',
            'name' => 'Spam Target',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        $this->assertEquals('valid', $contact->status);

        // 2. Submit single add request
        $url = 'http://admin.' . config('app.domain') . route('admin.blacklist.store', [], false);
        $response = $this->post($url, [
            'email' => 'spam-target@example.com',
            'reason' => 'Test single blacklist block',
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect();

        // 3. Verify blacklist record exists
        $this->assertDatabaseHas('blacklisted_emails', [
            'user_id' => $this->user->id,
            'email' => 'spam-target@example.com',
            'reason' => 'Test single blacklist block',
        ]);

        // 4. Verify contact is marked as invalid/blocked
        $contact->refresh();
        $this->assertEquals('invalid', $contact->status);
        $this->assertEquals('blocked', $contact->email_status);
        $this->assertEquals('unsubscribed', $contact->subscription_status);
        $this->assertEquals('Blacklisted email', $contact->validation_reason);
    }

    public function test_bulk_add_to_blacklist_supports_comma_space_and_newline_separators()
    {
        $this->actingAs($this->user);

        // 1. Create multiple contacts
        $contact1 = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'bulk1@example.com',
            'status' => 'valid',
        ]);

        $contact2 = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'bulk2@example.com',
            'status' => 'valid',
        ]);

        $contact3 = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'bulk3@example.com',
            'status' => 'valid',
        ]);

        // 2. Submit bulk add request with commas, spaces and newlines
        $url = 'http://admin.' . config('app.domain') . route('admin.blacklist.bulk-store', [], false);
        $response = $this->post($url, [
            'emails' => "bulk1@example.com,bulk2@example.com bulk3@example.com\nother@example.com",
            'reason' => 'Bulk test',
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect();

        // 3. Verify blacklist database entries exist
        $this->assertDatabaseHas('blacklisted_emails', ['email' => 'bulk1@example.com', 'user_id' => $this->user->id]);
        $this->assertDatabaseHas('blacklisted_emails', ['email' => 'bulk2@example.com', 'user_id' => $this->user->id]);
        $this->assertDatabaseHas('blacklisted_emails', ['email' => 'bulk3@example.com', 'user_id' => $this->user->id]);
        $this->assertDatabaseHas('blacklisted_emails', ['email' => 'other@example.com', 'user_id' => $this->user->id]);

        // 4. Verify existing contacts are marked blocked
        $contact1->refresh();
        $this->assertEquals('invalid', $contact1->status);
        $this->assertEquals('blocked', $contact1->email_status);

        $contact2->refresh();
        $this->assertEquals('invalid', $contact2->status);
        $this->assertEquals('blocked', $contact2->email_status);

        $contact3->refresh();
        $this->assertEquals('invalid', $contact3->status);
        $this->assertEquals('blocked', $contact3->email_status);
    }

    public function test_destroy_restores_contacts_to_valid()
    {
        $this->actingAs($this->user);

        // 1. Create a contact with blocked status
        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'unblock-me@example.com',
            'status' => 'invalid',
            'email_status' => 'blocked',
            'subscription_status' => 'unsubscribed',
            'validation_reason' => 'Blacklisted email',
        ]);

        // 2. Create blacklist entry
        $blacklistEntry = BlacklistedEmail::create([
            'user_id' => $this->user->id,
            'email' => 'unblock-me@example.com',
            'reason' => 'Temporary block',
        ]);

        // 3. Call destroy
        $url = 'http://admin.' . config('app.domain') . route('admin.blacklist.destroy', $blacklistEntry->id, false);
        $response = $this->delete($url, [], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect();

        // 4. Verify blacklist entry is deleted
        $this->assertDatabaseMissing('blacklisted_emails', ['id' => $blacklistEntry->id]);

        // 5. Verify contact is restored to valid
        $contact->refresh();
        $this->assertEquals('valid', $contact->status);
        $this->assertEquals('valid', $contact->email_status);
        $this->assertEquals('Removed from blacklist', $contact->validation_reason);
    }
}
