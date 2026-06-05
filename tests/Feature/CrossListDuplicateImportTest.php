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

        config('app.url');
        config('app.domain');

        $this->user = User::factory()->create();

        // Create an active subscription so user contact limits are not exceeded under test
        \App\Models\Subscription::create([
            'user_id' => $this->user->id,
            'plan_name' => 'Starter Plan',
            'contacts_limit' => 10000,
            'emails_limit' => 10000,
            'selected_modules' => ['crm', 'email', 'whatsapp'],
            'whatsapp_limit' => 1,
            'team_limit' => 1,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

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

    /**
     * Test space-separated country codes or spaces inside phone numbers (like +91 98765 43210)
     * are parsed as a single contact row (no row multiplier).
     */
    public function test_import_preserves_space_formatted_phone_numbers_without_multiplying()
    {
        $csvContent = "email,phone\n";
        $csvContent .= "test@example.com,+91 98765 43210\n";
        $path = 'temp_test_import.csv';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $csvContent);

        $parser = app(\App\Services\FileParserService::class);
        $mapping = [
            'email' => 'email',
            'whatsapp_number' => 'phone'
        ];

        $yielded = iterator_to_array($parser->streamStoredFile($path, $mapping));
        \Illuminate\Support\Facades\Storage::disk('local')->delete($path);

        $this->assertCount(1, $yielded);
        $this->assertEquals('test@example.com', $yielded[0]['email']);
        $this->assertEquals('919876543210', $yielded[0]['whatsapp_number']);
    }

    /**
     * Test importing an email that already has both a valid and duplicate record in the list
     * does not incorrectly promote the duplicate record to valid.
     */
    public function test_import_does_not_promote_existing_duplicates_when_valid_exists()
    {
        $this->actingAs($this->user);

        // 1. Create a valid record in List A
        $validContact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->listA->id,
            'email' => 'onlyone@example.com',
            'name' => 'Valid Contact',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
        ]);

        // 2. Create a duplicate record in List A
        $duplicateContact = Email::create([
            'user_id' => $this->user->id,
            'email_list_id' => $this->listA->id,
            'email' => 'onlyone@example.com',
            'name' => 'Duplicate Contact',
            'status' => 'duplicate',
            'subscription_status' => 'subscribed',
        ]);

        // 3. Import a chunk containing the same email
        $job = new ImportEmailChunkJob($this->listA->id, [
            ['email' => 'onlyone@example.com', 'name' => 'New Import Contact']
        ]);
        $job->handle($this->validator);

        // 4. Verify that the duplicate record is still status = 'duplicate'
        $duplicateContact->refresh();
        $this->assertEquals('duplicate', $duplicateContact->status);

        // 5. Verify there is still only 1 valid record for this email in List A
        $validCount = Email::where('email_list_id', $this->listA->id)
            ->where('email', 'onlyone@example.com')
            ->where('status', 'valid')
            ->count();
        $this->assertEquals(1, $validCount);
    }

    /**
     * Test list store validation fails with invalid file extension.
     */
    public function test_list_store_validation_fails_with_invalid_file_extension()
    {
        $this->actingAs($this->user);

        $file = \Illuminate\Http\UploadedFile::fake()->create('contacts.pdf', 100);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.store', [], false);
        
        $response = $this->post($url, [
            'name' => 'Test List',
            'import_type' => 'upload',
            'file' => $file,
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    /**
     * Test list store validation passes with valid file extensions.
     */
    public function test_list_store_validation_passes_with_valid_file_extensions()
    {
        $this->actingAs($this->user);

        $this->mock(\App\Services\FileParserService::class, function ($mock) {
            $mock->shouldReceive('parse')->andReturn([
                'headers' => ['email', 'name'],
                'rows' => [['email' => 'test@example.com', 'name' => 'Test']],
            ]);
            $mock->shouldReceive('autoDetectEmailColumn')->andReturn('email');
            $mock->shouldReceive('autoDetectNameColumn')->andReturn('name');
            $mock->shouldReceive('autoDetectMappings')->andReturn(['email' => 'email', 'name' => 'name']);
        });

        $file = \Illuminate\Http\UploadedFile::fake()->create('contacts.xlsx', 100);

        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.store', [], false);

        $response = $this->post($url, [
            'name' => 'Test List',
            'import_type' => 'upload',
            'file' => $file,
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertViewIs('email-lists.mapping');
    }

    /**
     * Test resolving all duplicates across pages via bulk action (selected_ids empty).
     */
    public function test_resolve_all_duplicates_across_pages_via_bulk_action()
    {
        $this->actingAs($this->user);

        // Create duplicate contacts in List B
        $contacts = [];
        for ($i = 1; $i <= 5; $i++) {
            $contacts[] = Email::create([
                'user_id' => $this->user->id,
                'email_list_id' => $this->listB->id,
                'email' => "dup{$i}@example.com",
                'name' => "Duplicate {$i}",
                'status' => 'cross_duplicate',
                'subscription_status' => 'subscribed',
            ]);
        }

        // Post resolution: bulk_action = 'keep_both', resolutions empty, selected_ids empty
        $url = 'http://admin.' . config('app.domain') . route('admin.email-lists.duplicates.resolve', $this->listB->id, false);
        $response = $this->post($url, [
            'bulk_action' => 'keep_both',
            'resolutions' => [],
            'selected_ids' => [],
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect();

        // Verify all 5 contacts are resolved to 'valid'
        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('emails', [
                'id' => $contact->id,
                'status' => 'valid',
            ]);
        }
    }
}
