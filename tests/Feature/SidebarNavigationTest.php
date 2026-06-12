<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_link_falls_back_to_index_when_no_workspaces()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        // Access create workspace page which uses layouts.app
        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.create', [], false);
        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        
        // Contacts link should fall back to index since there is no active workspace
        $indexUrl = route('admin.email-lists.index');
        $response->assertSee(e($indexUrl), false);
    }

    public function test_contacts_link_points_to_active_workspace_when_available()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        // Create workspace
        $workspace = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Active Workspace',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
            'is_public' => true,
        ]);

        // Set workspace as active in session
        session(['last_opened_list_id' => $workspace->id]);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.create', [], false);
        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();

        // Contacts link should now point to the show page of the workspace
        $showUrl = route('admin.email-lists.show', $workspace->id);
        $response->assertSee(e($showUrl), false);
    }

    public function test_workspace_switcher_redirects_to_new_workspace_show_page()
    {
        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $workspace1 = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Workspace One',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
            'is_public' => true,
        ]);

        $workspace2 = EmailList::create([
            'user_id' => $user->id,
            'name' => 'Workspace Two',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
            'is_public' => true,
        ]);

        // Simulate switching from workspace 1 show page (previous URL)
        $previousUrl = 'http://admin.' . config('app.domain') . route('admin.email-lists.show', $workspace1->id, false);

        $switchUrl = 'http://admin.' . config('app.domain') . route('admin.switch-workspace', $workspace2->id, false);

        $response = $this->from($previousUrl)->get($switchUrl, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        // It should redirect to workspace 2's show page
        $targetUrl = 'http://admin.' . config('app.domain') . route('admin.email-lists.show', $workspace2->id, false);
        $response->assertRedirect($targetUrl);
        
        // Session should be updated
        $this->assertEquals($workspace2->id, session('last_opened_list_id'));
    }
}
