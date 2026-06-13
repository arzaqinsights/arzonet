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
        \Illuminate\Support\Facades\Redis::shouldReceive('del')->zeroOrMoreTimes();
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

    public function test_signup_form_store_with_advanced_settings()
    {
        $url = 'http://admin.' . config('app.domain') . route('admin.signup-forms.store', [], false);

        $response = $this->post($url, [
            'name' => 'Advanced Newsletter',
            'title' => 'Subscribe!',
            'button_text' => 'Join Now',
            'theme_color' => '#ffffff',
            'allow_topic_selection' => 1,
            'tags' => 'Tag A, Tag B',
            'custom_fields' => [
                'name',
                'dyn_0' => [
                    'key' => 'custom_company',
                    'label' => 'Company',
                    'required' => '1',
                ]
            ],
            'subscribed_topics' => []
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect();
        
        $form = SignupForm::where('name', 'Advanced Newsletter')->first();
        $this->assertNotNull($form);
        $this->assertTrue($form->allow_topic_selection);
        $this->assertEquals(['Tag A', 'Tag B'], $form->tags);
        $this->assertCount(2, $form->custom_fields);
    }

    public function test_advanced_signup_form_submission()
    {
        $topic = \App\Models\SubscriptionTopic::create([
            'email_list_id' => $this->list->id,
            'name' => 'Newsletter Topic',
        ]);

        $form = SignupForm::create([
            'email_list_id' => $this->list->id,
            'user_id' => $this->user->id,
            'name' => 'Join our team',
            'token' => 'test-token-456',
            'title' => 'Sign up',
            'button_text' => 'Join',
            'theme_color' => '#000000',
            'allow_topic_selection' => true,
            'tags' => ['Tag C', 'Tag D'],
            'subscribed_topics' => [$topic->id],
            'custom_fields' => [
                'name',
                [
                    'key' => 'custom_company',
                    'label' => 'Company',
                    'required' => true,
                ]
            ]
        ]);

        $url = 'http://' . config('app.domain') . route('public.forms.submit', ['token' => $form->token], false);

        $response = $this->post($url, [
            'email' => 'subscriber@example.com',
            'name' => 'Jane Subscriber',
            'custom_company' => 'Awesome Corp',
            'topics' => [$topic->id]
        ], [
            'Host' => config('app.domain')
        ]);

        $response->assertSessionHasNoErrors();
        
        $contact = \App\Models\Email::where('email', 'subscriber@example.com')->first();
        $this->assertNotNull($contact);
        $this->assertEquals('Jane Subscriber', $contact->name);
        $this->assertEquals('From Link: Join our team', $contact->signup_source);
        $this->assertContains('Tag C', $contact->tags);
        $this->assertContains('Tag D', $contact->tags);
        $this->assertEquals([$topic->id], $contact->subscribed_topics);
        $this->assertEquals('Awesome Corp', $contact->meta['custom_company'] ?? null);
    }
}
