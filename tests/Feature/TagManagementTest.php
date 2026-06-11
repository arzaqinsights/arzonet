<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagManagementTest extends TestCase
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
            'name' => 'Tags Test List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        session(['last_opened_list_id' => $this->emailList->id]);
    }

    public function test_tag_index_shows_tags()
    {
        $this->actingAs($this->user);

        Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact1@example.com',
            'tags' => ['VIP', 'Founder']
        ]);

        Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact2@example.com',
            'tags' => ['Founder']
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.tags.index', [], false);

        $response = $this->withSession(['last_opened_list_id' => $this->emailList->id])
            ->get($url, ['Host' => 'admin.' . config('app.domain')]);

        $response->assertOk();
        $response->assertViewHas('tags');
        
        $tags = $response->viewData('tags');
        $this->assertCount(2, $tags);
        
        $founderTag = collect($tags)->firstWhere('name', 'Founder');
        $vipTag = collect($tags)->firstWhere('name', 'VIP');

        $this->assertEquals(2, $founderTag['contact_count']);
        $this->assertEquals(1, $vipTag['contact_count']);
    }

    public function test_tag_rename()
    {
        $this->actingAs($this->user);

        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact1@example.com',
            'tags' => ['VIP', 'Founder']
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.tags.rename', [], false);

        $response = $this->withSession(['last_opened_list_id' => $this->emailList->id])
            ->post($url, [
                'old_name' => 'Founder',
                'new_name' => 'CEO'
            ], ['Host' => 'admin.' . config('app.domain')]);

        $response->assertRedirect();
        
        $contact->refresh();
        $this->assertEquals(['VIP', 'CEO'], $contact->tags);
    }

    public function test_tag_merge()
    {
        $this->actingAs($this->user);

        $contact1 = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact1@example.com',
            'tags' => ['Founder']
        ]);

        $contact2 = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact2@example.com',
            'tags' => ['CEO']
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.tags.merge', [], false);

        $response = $this->withSession(['last_opened_list_id' => $this->emailList->id])
            ->post($url, [
                'source_tag' => 'Founder',
                'target_tag' => 'CEO'
            ], ['Host' => 'admin.' . config('app.domain')]);

        $response->assertRedirect();

        $contact1->refresh();
        $contact2->refresh();

        $this->assertEquals(['CEO'], $contact1->tags);
        $this->assertEquals(['CEO'], $contact2->tags);
    }

    public function test_tag_delete()
    {
        $this->actingAs($this->user);

        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'contact1@example.com',
            'tags' => ['VIP', 'Founder']
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.tags.delete', [], false);

        $response = $this->withSession(['last_opened_list_id' => $this->emailList->id])
            ->post($url, [
                'tag' => 'Founder'
            ], ['Host' => 'admin.' . config('app.domain')]);

        $response->assertRedirect();

        $contact->refresh();
        $this->assertEquals(['VIP'], $contact->tags);
    }
}
