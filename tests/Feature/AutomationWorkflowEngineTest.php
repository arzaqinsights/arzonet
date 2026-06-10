<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Models\Template;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\PreferenceLog;
use App\Models\Segment;
use App\Jobs\ProcessAutomationWorkflowsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class AutomationWorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected EmailList $emailList;
    protected Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->emailList = EmailList::create([
            'user_id' => $this->user->id,
            'name' => 'Automated Journey List',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
            'signup_form_token' => 'test_form_token_123',
        ]);

        $this->template = Template::create([
            'user_id' => $this->user->id,
            'name' => 'Welcome Template',
            'html_content' => '<h1>Hello, [name]!</h1>',
        ]);
    }

    public function test_public_signup_form_single_opt_in()
    {
        // Ensure double opt in is off
        $this->emailList->update(['double_opt_in' => false]);

        // Create an active workflow for signups
        $workflow = Workflow::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Signup Journey',
            'trigger_type' => 'list_signup',
            'nodes' => [
                'start' => ['type' => 'add_tag', 'details' => ['tag' => 'new_subscriber'], 'next' => null]
            ],
            'is_active' => true,
        ]);

        $url = route('public.forms.submit', ['token' => $this->emailList->signup_form_token]);

        $response = $this->post($url, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(302);
        
        // Assert contact is created and subscribed
        $contact = Email::where('email_list_id', $this->emailList->id)
            ->where('email', 'john@example.com')
            ->first();

        $this->assertNotNull($contact);
        $this->assertEquals('subscribed', $contact->subscription_status);
        $this->assertEquals('John Doe', $contact->name);

        // Assert preference log is written
        $this->assertTrue(PreferenceLog::where('email_id', $contact->id)->exists());

        // Assert workflow run is triggered
        $this->assertTrue(WorkflowRun::where('workflow_id', $workflow->id)
            ->where('email_id', $contact->id)
            ->where('status', 'active')
            ->exists());
    }

    public function test_public_signup_form_double_opt_in()
    {
        // Enable double opt in
        $this->emailList->update(['double_opt_in' => true]);

        $workflow = Workflow::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Signup Journey',
            'trigger_type' => 'list_signup',
            'nodes' => [
                'start' => ['type' => 'add_tag', 'details' => ['tag' => 'verified_subscriber'], 'next' => null]
            ],
            'is_active' => true,
        ]);

        $url = route('public.forms.submit', ['token' => $this->emailList->signup_form_token]);

        $response = $this->post($url, [
            'name' => 'Alice Green',
            'email' => 'alice@example.com',
        ]);

        $response->assertStatus(302);

        // Contact should be pending
        $contact = Email::where('email_list_id', $this->emailList->id)
            ->where('email', 'alice@example.com')
            ->first();

        $this->assertNotNull($contact);
        $this->assertEquals('pending', $contact->subscription_status);

        // Workflow run should NOT be triggered yet
        $this->assertFalse(WorkflowRun::where('workflow_id', $workflow->id)
            ->where('email_id', $contact->id)
            ->exists());

        // Verify confirmation endpoint
        $confirmToken = Crypt::encryptString($contact->id);
        $confirmUrl = route('public.confirm-subscription', ['token' => $confirmToken]);

        $confirmResponse = $this->get($confirmUrl);
        $confirmResponse->assertStatus(200);

        // Contact should now be subscribed
        $contact->refresh();
        $this->assertEquals('subscribed', $contact->subscription_status);

        // Workflow run should now be triggered
        $this->assertTrue(WorkflowRun::where('workflow_id', $workflow->id)
            ->where('email_id', $contact->id)
            ->where('status', 'active')
            ->exists());
    }

    public function test_workflow_steps_execution()
    {
        Mail::fake();

        $contact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'bob@example.com',
            'name' => 'Bob Marley',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Create a 3-step workflow: Wait -> Send Email -> Add Tag
        $workflow = Workflow::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'Multi-step Automation',
            'trigger_type' => 'list_signup',
            'nodes' => [
                'start' => ['type' => 'wait', 'details' => ['delay' => 10, 'unit' => 'minutes'], 'next' => 'step2'],
                'step2' => ['type' => 'send_email', 'details' => ['template_id' => $this->template->id, 'subject' => 'Welcome to our newsletter!'], 'next' => 'step3'],
                'step3' => ['type' => 'add_tag', 'details' => ['tag' => 'auto_processed'], 'next' => null],
            ],
            'is_active' => true,
        ]);

        // Trigger the workflow manually
        Workflow::trigger('list_signup', $contact);

        $run = WorkflowRun::where('workflow_id', $workflow->id)
            ->where('email_id', $contact->id)
            ->first();

        $this->assertNotNull($run);
        $this->assertEquals('start', $run->current_node_id);
        $this->assertEquals('active', $run->status);

        // ── STEP 1: Wait Execution ──
        $job = new ProcessAutomationWorkflowsJob();
        $job->handle(app(\App\Services\MailService::class));

        $run->refresh();
        // Index is transitioned to step2, scheduled_at pushed into future (+10 mins)
        $this->assertEquals('step2', $run->current_node_id);
        $this->assertEquals('active', $run->status);
        $this->assertTrue($run->scheduled_at->isFuture());

        // ── STEP 2: Send Email Execution ──
        // Cheat scheduled time to the past so it can run
        $run->update(['scheduled_at' => now()->subMinutes(1)]);

        $job->handle(app(\App\Services\MailService::class));

        $run->refresh();
        $this->assertEquals('step3', $run->current_node_id);
        $this->assertEquals('active', $run->status);

        // ── STEP 3: Tag Execution ──
        $run->update(['scheduled_at' => now()->subMinutes(1)]);

        $job->handle(app(\App\Services\MailService::class));

        $run->refresh();
        // Since step transitions to null, status becomes completed
        $this->assertEquals('completed', $run->status);

        // Contact should have the tag "auto_processed" added
        $contact->refresh();
        $this->assertContains('auto_processed', $contact->tags ?? []);
    }

    public function test_dynamic_segment_evaluation()
    {
        $john = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'tags' => ['VIP', 'Developer'],
        ]);

        $bob = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'email' => 'bob@example.com',
            'name' => 'Bob Marley',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'tags' => ['Developer'],
        ]);

        $segment = Segment::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->emailList->id,
            'name' => 'VIP Johns',
            'rules' => [
                ['field' => 'name', 'operator' => 'contains', 'value' => 'John'],
                ['field' => 'tag', 'operator' => 'equals', 'value' => 'VIP']
            ]
        ]);

        // In-memory matches
        $this->assertTrue($segment->matchesContact($john));
        $this->assertFalse($segment->matchesContact($bob));

        // Query matches
        $query = Email::query()->where('email_list_id', $this->emailList->id);
        $query = Segment::applyRulesToQuery($query, $segment->rules);
        $results = $query->get();

        $this->assertEquals(1, $results->count());
        $this->assertEquals($john->id, $results->first()->id);
    }
}
