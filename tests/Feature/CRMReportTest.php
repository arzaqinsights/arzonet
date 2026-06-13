<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailList;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CRMReportTest extends TestCase
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

        $this->list = EmailList::create([
            'user_id' => $this->user->id,
            'name' => 'Reports test list',
            'list_type' => EmailList::TYPE_EMAIL,
            'status' => 'completed',
        ]);

        session(['last_opened_list_id' => $this->list->id]);
        \Illuminate\Support\Facades\Redis::shouldReceive('del')->zeroOrMoreTimes();
    }

    public function test_reports_page_loads_with_empty_state_when_no_pipelines_exist()
    {
        $url = 'http://admin.' . config('app.domain') . route('admin.crm-reports.index', [], false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $response->assertSee('No Pipelines Found');
    }

    public function test_reports_page_loads_successfully_with_pipeline_data()
    {
        $pipeline = Pipeline::create([
            'name' => 'Sales Pipeline',
            'email_list_id' => $this->list->id,
            'user_id' => $this->user->id,
            'created_by_id' => $this->user->id,
        ]);

        // Boot observer auto-creates default stages (Lead, Contacted, Proposal Sent, Won, Lost)
        $wonStage = $pipeline->stages()->where('name', 'Won')->first();
        
        // Let's create a deal
        Deal::create([
            'pipeline_stage_id' => $wonStage->id,
            'title' => 'Big Enterprise Deal',
            'value' => 50000.00,
            'status' => 'won',
            'currency' => 'INR',
            'expected_close_at' => now()->addDays(15),
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.crm-reports.index', ['pipeline_id' => $pipeline->id], false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertOk();
        $response->assertSee('Sales Pipeline');
        $response->assertSee('Sales Representative Leaderboard');
        $response->assertSee('50,000');
    }
}
