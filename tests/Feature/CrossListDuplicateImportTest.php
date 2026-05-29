<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Email;
use App\Services\EmailValidationService;
use App\Jobs\ImportEmailChunkJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CrossListDuplicateImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected EmailList $listA;
    protected EmailList $listB;
    protected EmailValidationService $validator;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $this->user = User::factory()->create();
        $this->validator = app(EmailValidationService::class);

        // Create two lists
        $this->listA = EmailList::create([
            'user_id' => $this->user->id,
            'name' => 'List A',
            'list_type' => EmailList::TYPE_DUAL,
            'status' => 'completed',
        ]);

        $this->listB = EmailList::create([
            'user_id' => $this->user->id,
            'name' => 'List B',
            'list_type' => EmailList::TYPE_DUAL,
            'status' => 'completed',
        ]);
    }

    /**
     * Test validation detects duplicates in other lists and assigns cross_duplicate status.
     */
    public function test_validation_detects_cross_list_duplicates()
    {
        $this->actingAs($this->user);

        // Contact exists in List A
        Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->listA->id,
            'email' => 'duplicate@example.com',
            'name' => 'Duplicate Contact',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Validate importing the same contact into List B
        $importData = [
            ['email' => 'duplicate@example.com', 'name' => 'Duplicate Contact']
        ];

        $results = $this->validator->validateBatch($importData, $this->listB->id);

        $this->assertCount(1, $results['cross_duplicate']);
        $this->assertCount(0, $results['valid']);

        $crossDup = $results['cross_duplicate'][0];
        $this->assertEquals('cross_duplicate', $crossDup['status']);
        $this->assertNotEmpty($crossDup['meta']['cross_list_duplicates']);
        $this->assertEquals($this->listA->id, $crossDup['meta']['cross_list_duplicates'][0]['list_id']);
    }

    /**
     * Test resolving duplicates as keep_old (do not import/delete new entry).
     */
    public function test_resolves_duplicate_as_keep_old()
    {
        $this->actingAs($this->user);

        // Contact exists in List A
        $contactA = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->listA->id,
            'email' => 'duplicate@example.com',
            'name' => 'Duplicate Contact',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Import chunk job places duplicate as cross_duplicate in List B
        $job = new ImportEmailChunkJob($this->listB->id, [
            ['email' => 'duplicate@example.com', 'name' => 'Duplicate Contact']
        ]);
        $job->handle($this->validator);

        $contactB = Email::where('email_list_id', $this->listB->id)->where('email', 'duplicate@example.com')->first();
        $this->assertNotNull($contactB);
        $this->assertEquals('cross_duplicate', $contactB->status);

        // Resolve action: keep_old
        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.duplicates.resolve', $this->listB->id, false);
        $response = $this->post($url, [
            'resolutions' => [
                $contactB->id => 'keep_old'
            ]
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect();

        // Contact B should be deleted, Contact A remains
        $this->assertDatabaseMissing('emails', ['id' => $contactB->id]);
        $this->assertDatabaseHas('emails', ['id' => $contactA->id]);
    }

    /**
     * Test resolving duplicates as move_new (delete from old, mark valid here).
     */
    public function test_resolves_duplicate_as_move_new()
    {
        $this->actingAs($this->user);

        // Contact exists in List A
        $contactA = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->listA->id,
            'email' => 'duplicate@example.com',
            'name' => 'Duplicate Contact',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Import chunk job places duplicate in List B
        $job = new ImportEmailChunkJob($this->listB->id, [
            ['email' => 'duplicate@example.com', 'name' => 'Duplicate Contact']
        ]);
        $job->handle($this->validator);

        $contactB = Email::where('email_list_id', $this->listB->id)->where('email', 'duplicate@example.com')->first();

        // Resolve action: move_new
        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.duplicates.resolve', $this->listB->id, false);
        $response = $this->post($url, [
            'resolutions' => [
                $contactB->id => 'move_new'
            ]
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect();

        // Contact A should be deleted, Contact B should be valid
        $this->assertDatabaseMissing('emails', ['id' => $contactA->id]);
        $this->assertDatabaseHas('emails', ['id' => $contactB->id, 'status' => 'valid']);
    }

    /**
     * Test resolving duplicates as keep_both (both stay valid).
     */
    public function test_resolves_duplicate_as_keep_both()
    {
        $this->actingAs($this->user);

        // Contact exists in List A
        $contactA = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->listA->id,
            'email' => 'duplicate@example.com',
            'name' => 'Duplicate Contact',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // Import chunk job places duplicate in List B
        $job = new ImportEmailChunkJob($this->listB->id, [
            ['email' => 'duplicate@example.com', 'name' => 'Duplicate Contact']
        ]);
        $job->handle($this->validator);

        $contactB = Email::where('email_list_id', $this->listB->id)->where('email', 'duplicate@example.com')->first();

        // Resolve action: keep_both
        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.duplicates.resolve', $this->listB->id, false);
        $response = $this->post($url, [
            'resolutions' => [
                $contactB->id => 'keep_both'
            ]
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect();

        // Both contacts should remain valid in the database
        $this->assertDatabaseHas('emails', ['id' => $contactA->id, 'status' => 'valid']);
        $this->assertDatabaseHas('emails', ['id' => $contactB->id, 'status' => 'valid']);
    }
}
