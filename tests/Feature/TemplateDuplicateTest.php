<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $this->user = User::factory()->create(['role' => 'admin']);
    }

    /**
     * Test a template can be successfully duplicated.
     */
    public function test_template_can_be_duplicated()
    {
        $this->actingAs($this->user);

        // Create initial template
        $template = Template::create([
            'user_id' => $this->user->id,
            'name' => 'Newsletter Template',
            'html_content' => '<h1>Hello {{name}}</h1>',
            'json_design' => '{"design": true}'
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.templates.clone', $template, false);
        
        $response = $this->post($url, [], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect(route('admin.templates.index'));

        // Verify copy exists in the database
        $this->assertDatabaseHas('templates', [
            'user_id' => $this->user->id,
            'name' => 'Newsletter Template (Copy)',
            'html_content' => '<h1>Hello {{name}}</h1>',
            'json_design' => '{"design": true}'
        ]);

        // Total templates count should be 2
        $this->assertEquals(2, Template::count());
    }
}
