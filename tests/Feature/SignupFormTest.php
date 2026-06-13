<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\SignupForm;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SignupFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected EmailList $list;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);

        // Create a list with array-based setting inside column_mapping
        $this->list = EmailList::create([
            'user_id' => $this->user->id,
            'name' => 'Signup test list',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
            'column_mapping' => [
                'email' => 'email',
                'First Name' => 'custom_1',
                '_settings' => [
                    'skip_dns' => true
                ]
            ]
        ]);

        session(['last_opened_list_id' => $this->list->id]);
    }

    public function test_signup_form_creation_page_loads_without_type_error()
    {
        $url = 'http://admin.' . config('app.domain') . route('admin.signup-forms.create', [], false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $response->assertSee('First Name'); // Verification that custom fields were resolved correctly
    }

    public function test_public_signup_page_loads_without_type_error()
    {
        $form = SignupForm::create([
            'email_list_id' => $this->list->id,
            'user_id' => $this->user->id,
            'name' => 'Join our team',
            'token' => 'test-token-123',
            'title' => 'Sign up',
            'button_text' => 'Join',
            'theme_color' => '#000000',
            'custom_fields' => ['custom_1']
        ]);

        $url = 'http://admin.' . config('app.domain') . route('public.forms.show', ['token' => $form->token], false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
    }
}
