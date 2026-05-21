<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CRMContactGroupingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected EmailList $emailList;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin the domain configurations to guarantee matching under test environments
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        // Create a user
        $this->user = User::factory()->create();

        // Create an email list
        $this->emailList = EmailList::create([
            'user_id' => $this->user->id,
            'name' => 'Test List',
            'list_type' => EmailList::TYPE_DUAL,
            'status' => 'completed',
        ]);
    }

    /**
     * Test grouping sync of shared metadata when original_row_id matches.
     */
    public function test_updates_propagate_to_same_original_row_id_group()
    {
        $this->actingAs($this->user);
        $this->withoutExceptionHandling();

        // Create master and sub-row sharing original_row_id
        $master = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'whatsapp_number' => '1234567890',
            'segment_name' => 'Tech',
            'tags' => ['VIP'],
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'original_row_id' => 'group_123',
            'meta' => ['company' => 'Google', 'job_title' => 'Engineer']
        ]);

        $sub = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'johndoe.alt@example.com',
            'name' => 'John Doe',
            'whatsapp_number' => '0987654321',
            'segment_name' => 'Tech',
            'tags' => ['VIP'],
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'original_row_id' => 'group_123',
            'meta' => ['company' => 'Google', 'job_title' => 'Engineer']
        ]);

        // Generate full subdomain URL
        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.update-email', ['emailList' => $this->emailList->id, 'emailId' => $master->id], false);

        // Put request to update the master record
        $response = $this->put($url, [
            'email' => 'john.doe.new@example.com',
            'name' => 'Johnathan Doe',
            'whatsapp_number' => '1234567890',
            'segment_name' => 'Sales',
            'tags' => 'Enterprise,VIP',
            'meta' => ['company' => 'Alphabet', 'job_title' => 'Director']
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Refresh models
        $master->refresh();
        $sub->refresh();

        // Master should have updated email and updated metadata
        $this->assertEquals('john.doe.new@example.com', $master->email);
        $this->assertEquals('Johnathan Doe', $master->name);
        $this->assertEquals('Sales', $master->segment_name);
        $this->assertEquals(['Enterprise', 'VIP'], $master->tags);
        $this->assertEquals('Alphabet', $master->meta['company']);

        // Sub should have original email but UPDATED metadata
        $this->assertEquals('johndoe.alt@example.com', $sub->email);
        $this->assertEquals('Johnathan Doe', $sub->name);
        $this->assertEquals('Sales', $sub->segment_name);
        $this->assertEquals(['Enterprise', 'VIP'], $sub->tags);
        $this->assertEquals('Alphabet', $sub->meta['company']);
    }

    /**
     * Test linking contacts via name fallback if original_row_id is null.
     */
    public function test_links_contacts_by_name_and_syncs_with_uuid()
    {
        $this->actingAs($this->user);

        // Create two contacts with same name but original_row_id is null
        $contact1 = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'alice@example.com',
            'name' => 'Alice Smith',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'original_row_id' => null,
            'meta' => ['company' => 'Microsoft']
        ]);

        $contact2 = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'alice.smith@example.com',
            'name' => 'Alice Smith',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'original_row_id' => null,
            'meta' => ['company' => 'Microsoft']
        ]);

        // Generate full subdomain URL
        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.update-email', ['emailList' => $this->emailList->id, 'emailId' => $contact1->id], false);

        // Put request to update the first contact
        $response = $this->put($url, [
            'email' => 'alice.new@example.com',
            'name' => 'Alice Jones',
            'segment_name' => 'Marketing',
            'tags' => 'Lead',
            'meta' => ['company' => 'OpenAI']
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();

        // Refresh models
        $contact1->refresh();
        $contact2->refresh();

        // Both contacts should now share a newly generated original_row_id (UUID)
        $this->assertNotEmpty($contact1->original_row_id);
        $this->assertEquals($contact1->original_row_id, $contact2->original_row_id);

        // Both contacts should have the updated name and metadata
        $this->assertEquals('Alice Jones', $contact1->name);
        $this->assertEquals('Alice Jones', $contact2->name);
        $this->assertEquals('Marketing', $contact2->segment_name);
        $this->assertEquals(['Lead'], $contact2->tags);
        $this->assertEquals('OpenAI', $contact2->meta['company']);
    }
}
