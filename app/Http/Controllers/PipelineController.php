<?php

namespace App\Http\Controllers;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Deal;
use App\Models\DealActivity;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PipelineController extends Controller
{
    public function index()
    {
        $activeWorkspaceId = session('last_opened_list_id');
        $query = Pipeline::with(['stages', 'creator'])->withCount([
            'deals',
            'deals as open_deals_count' => function ($q) { $q->where('status', 'open'); },
            'deals as won_deals_count' => function ($q) { $q->where('status', 'won'); },
            'deals as lost_deals_count' => function ($q) { $q->where('status', 'lost'); }
        ])->withSum('deals', 'value');

        if ($activeWorkspaceId) {
            $query->where(function ($q) use ($activeWorkspaceId) {
                $q->where('email_list_id', $activeWorkspaceId)
                  ->orWhereNull('email_list_id');
            });
        }

        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            $query->where(function($q) use ($teamUserId) {
                $q->where('is_public', true)
                  ->orWhere('created_by_id', $teamUserId);
            });
        }
        $pipelines = $query->latest()->get();

        $pipelines->each(function($pipeline) {
            $pipeline->recent_deals = $pipeline->deals()->with('contact')->latest()->take(4)->get();
        });

        $user = auth()->user();
        $teamMembers = collect();
        if ($user) {
            $admin = $user->parent_id ? \App\Models\User::find($user->parent_id) : $user;
            if ($admin) {
                $teamMembers = $admin->teamMembers()->get();
                if ($user->parent_id) {
                    $teamMembers->push($admin);
                }
            }
        }

        return view('crm.pipelines.index', compact('pipelines', 'teamMembers'));
    }

    public function create()
    {
        return view('crm.pipelines.create');
    }

    public function store(Request $request)
    {
        $activeWorkspaceId = session('last_opened_list_id');
        $request->validate([
            'name' => 'required|string|max:255',
            'is_public' => 'nullable',
        ]);

        $isPublic = $request->has('is_public') ? (bool) $request->is_public : true;

        $teamPermissions = [
            'add_deal' => $request->has('team_permissions.add_deal'),
            'edit_deal' => $request->has('team_permissions.edit_deal'),
            'delete_deal' => $request->has('team_permissions.delete_deal'),
            'move_deal' => $request->has('team_permissions.move_deal'),
        ];

        $pipeline = Pipeline::create([
            'name' => $request->name,
            'is_public' => $isPublic,
            'created_by_id' => app()->has('team_user') ? app('team_user')->id : auth()->id(),
            'team_permissions' => $teamPermissions,
            'email_list_id' => $activeWorkspaceId,
        ]);

        return redirect()
            ->route('admin.pipelines.show', $pipeline)
            ->with('success', 'Pipeline created with default stages.');
    }

    public function show(Pipeline $pipeline)
    {
        $activeWorkspaceId = session('last_opened_list_id');
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                abort(403, 'This pipeline is private.');
            }
        }

        $pipeline->load(['stages.deals.contact', 'stages.deals.assignee']);
        
        $contactsQuery = Email::select('id', 'name', 'email', 'whatsapp_number');
        if ($activeWorkspaceId) {
            $contactsQuery->where('email_list_id', $activeWorkspaceId);
        }
        $contacts = $contactsQuery->limit(500)->get();

        // Get team members for assignee dropdown
        $user = auth()->user();
        $teamMembers = collect();
        if ($user) {
            $admin = $user->parent_id ? \App\Models\User::find($user->parent_id) : $user;
            if ($admin) {
                $teamMembers = $admin->teamMembers()->get();
                if ($user->parent_id) {
                    $teamMembers->push($admin);
                } else {
                    $teamMembers->push($admin);
                }
            }
        }

        return view('crm.pipelines.show', compact('pipeline', 'contacts', 'teamMembers'));
    }

    // ──────────────────────────────────────────────
    // Stage Management
    // ──────────────────────────────────────────────

    public function addStage(Request $request, Pipeline $pipeline)
    {
        $this->authorizeOwner($pipeline);

        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        $maxOrder = $pipeline->stages()->max('order') ?? -1;

        $stage = $pipeline->stages()->create([
            'name' => $request->name,
            'color' => $request->color ?? '#6366f1',
            'order' => $maxOrder + 1,
            'user_id' => $pipeline->user_id,
        ]);

        return response()->json(['success' => true, 'stage' => $stage]);
    }

    public function updateStage(Request $request, PipelineStage $stage)
    {
        $this->authorizeOwner($stage->pipeline);

        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'automation_action' => 'nullable|string|in:tag_contact,subscribe_email,unsubscribe_email',
            'automation_value' => 'nullable|string|max:255',
        ]);

        $stage->update($request->only(['name', 'color', 'automation_action', 'automation_value']));

        return response()->json(['success' => true]);
    }

    public function deleteStage(PipelineStage $stage)
    {
        $pipeline = $stage->pipeline;
        $this->authorizeOwner($pipeline);

        // Move deals to first remaining stage
        $firstStage = $pipeline->stages()->where('id', '!=', $stage->id)->orderBy('order')->first();
        if ($firstStage) {
            $stage->deals()->update(['pipeline_stage_id' => $firstStage->id]);
        } else {
            // Last stage — delete its deals too
            $stage->deals()->delete();
        }

        $stage->delete();

        return response()->json(['success' => true]);
    }

    public function reorderStages(Request $request, Pipeline $pipeline)
    {
        $this->authorizeOwner($pipeline);

        $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:pipeline_stages,id',
            'stages.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->stages as $stageData) {
            PipelineStage::where('id', $stageData['id'])
                ->where('pipeline_id', $pipeline->id)
                ->update(['order' => $stageData['order']]);
        }

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────
    // Deal CRUD with Activity Logging
    // ──────────────────────────────────────────────

    /**
     * AJAX: Move deal to a different stage (drag-and-drop).
     */
    public function updateDealStage(Request $request)
    {
        $request->validate([
            'deal_id'  => 'required|exists:deals,id',
            'stage_id' => 'required|exists:pipeline_stages,id',
            'order'    => 'required|integer|min:0',
        ]);

        $deal = Deal::findOrFail($request->deal_id);
        $pipeline = $deal->stage->pipeline;

        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                abort(403, 'This pipeline is private.');
            }
        }
        if (!$pipeline->canPerformAction('move_deal')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to move deals in this pipeline.'], 403);
        }

        $oldStage = $deal->stage;
        $newStage = PipelineStage::findOrFail($request->stage_id);

        \Illuminate\Support\Facades\DB::transaction(function() use ($deal, $request, $oldStage, $newStage) {
            $deal->update([
                'pipeline_stage_id' => $request->stage_id,
                'order' => $request->order,
            ]);

            // Re-index target stage deals to accommodate the new order and avoid collisions
            $newStageDeals = Deal::where('pipeline_stage_id', $newStage->id)
                ->where('id', '!=', $deal->id)
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            $mergedDeals = $newStageDeals->all();
            $targetIndex = min((int)$request->order, count($mergedDeals));
            array_splice($mergedDeals, $targetIndex, 0, [$deal]);

            foreach ($mergedDeals as $i => $d) {
                if ($d->order !== $i) {
                    $d->update(['order' => $i]);
                }
            }

            // Re-index old stage deals if the deal moved stages
            if ($oldStage->id !== $newStage->id) {
                $oldStageDeals = Deal::where('pipeline_stage_id', $oldStage->id)
                    ->where('id', '!=', $deal->id)
                    ->orderBy('order')
                    ->orderBy('id')
                    ->get();
                foreach ($oldStageDeals as $i => $d) {
                    if ($d->order !== $i) {
                        $d->update(['order' => $i]);
                    }
                }
            }
        });

        // Log activity
        if ($oldStage->id !== $newStage->id) {
            DealActivity::create([
                'deal_id' => $deal->id,
                'user_id' => auth()->id(),
                'type' => 'moved',
                'description' => "Moved from \"{$oldStage->name}\" to \"{$newStage->name}\"",
                'old_value' => $oldStage->name,
                'new_value' => $newStage->name,
            ]);
        }

        // Auto-update status based on stage name
        $stageLower = strtolower($newStage->name);
        if ($stageLower === 'won') {
            $oldStatus = $deal->status;
            $deal->update(['status' => 'won']);
            if ($oldStatus !== 'won') {
                DealActivity::create([
                    'deal_id' => $deal->id,
                    'user_id' => auth()->id(),
                    'type' => 'status_changed',
                    'description' => "Status changed to Won",
                    'old_value' => $oldStatus,
                    'new_value' => 'won',
                ]);
            }
        } elseif ($stageLower === 'lost') {
            $oldStatus = $deal->status;
            $deal->update(['status' => 'lost']);
            if ($oldStatus !== 'lost') {
                DealActivity::create([
                    'deal_id' => $deal->id,
                    'user_id' => auth()->id(),
                    'type' => 'status_changed',
                    'description' => "Status changed to Lost",
                    'old_value' => $oldStatus,
                    'new_value' => 'lost',
                ]);
            }
        } elseif (in_array($deal->status, ['won', 'lost'])) {
            $deal->update(['status' => 'open']);
        }

        // Run Stage Automations
        if ($newStage->automation_action && $deal->email_id) {
            $contact = $deal->contact;
            if ($contact) {
                if ($newStage->automation_action === 'tag_contact' && $newStage->automation_value) {
                    $currentTags = $contact->tags ?? [];
                    if (!in_array($newStage->automation_value, $currentTags)) {
                        $currentTags[] = $newStage->automation_value;
                        $contact->update(['tags' => $currentTags]);
                    }
                } elseif ($newStage->automation_action === 'subscribe_email') {
                    $contact->update(['subscription_status' => 'subscribed']);
                } elseif ($newStage->automation_action === 'unsubscribe_email') {
                    $contact->update(['subscription_status' => 'unsubscribed']);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function storeDeal(Request $request, Pipeline $pipeline)
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                abort(403, 'This pipeline is private.');
            }
        }
        if (!$pipeline->canPerformAction('add_deal')) {
            return redirect()->back()->with('error', 'You do not have permission to add deals to this pipeline.');
        }

        $request->validate([
            'title'             => 'required|string|max:255',
            'value'             => 'nullable|numeric|min:0',
            'email_id'          => 'nullable|exists:emails,id',
            'assigned_to_id'    => 'nullable|exists:users,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'expected_close_at' => 'nullable|date',
            'notes'             => 'nullable|string',
            'tags'              => 'nullable|string',
        ]);

        $tags = $request->tags ? array_filter(array_map('trim', explode(',', $request->tags))) : [];

        $deal = Deal::create([
            'pipeline_stage_id' => $request->pipeline_stage_id,
            'email_id'          => $request->email_id,
            'assigned_to_id'    => $request->assigned_to_id,
            'title'             => $request->title,
            'value'             => $request->value ?? 0,
            'currency'          => 'INR',
            'status'            => 'open',
            'expected_close_at' => $request->expected_close_at,
            'notes'             => $request->notes,
            'tags'              => $tags,
            'order'             => Deal::where('pipeline_stage_id', $request->pipeline_stage_id)->count(),
        ]);

        // Log creation activity
        DealActivity::create([
            'deal_id' => $deal->id,
            'user_id' => auth()->id(),
            'type' => 'created',
            'description' => "Deal \"{$deal->title}\" created with value ₹" . number_format($deal->value),
        ]);

        if ($request->assigned_to_id) {
            $assignee = \App\Models\User::find($request->assigned_to_id);
            DealActivity::create([
                'deal_id' => $deal->id,
                'user_id' => auth()->id(),
                'type' => 'assigned',
                'description' => "Assigned to " . ($assignee->name ?? 'Unknown'),
                'new_value' => $assignee->name ?? null,
            ]);
        }

        return redirect()
            ->route('admin.pipelines.show', $pipeline)
            ->with('success', 'Deal added successfully.');
    }

    public function updateDeal(Request $request, Deal $deal)
    {
        $pipeline = $deal->stage->pipeline;
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                abort(403, 'This pipeline is private.');
            }
        }
        if (!$pipeline->canPerformAction('edit_deal')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to edit deals in this pipeline.'], 403);
        }

        $request->validate([
            'title'             => 'required|string|max:255',
            'value'             => 'nullable|numeric|min:0',
            'status'            => 'nullable|in:open,won,lost',
            'email_id'          => 'nullable|exists:emails,id',
            'assigned_to_id'    => 'nullable|exists:users,id',
            'expected_close_at' => 'nullable|date',
            'notes'             => 'nullable|string',
            'tags'              => 'nullable|string',
        ]);

        $tags = $request->has('tags') ? array_filter(array_map('trim', explode(',', $request->tags))) : $deal->tags;

        // Build change description for activity log
        $changes = [];
        if ($request->title !== $deal->title) $changes[] = "Title: \"{$deal->title}\" → \"{$request->title}\"";
        if ((float)$request->value != (float)$deal->value) $changes[] = "Value: ₹" . number_format($deal->value) . " → ₹" . number_format($request->value);
        if ($request->status && $request->status !== $deal->status) $changes[] = "Status: {$deal->status} → {$request->status}";

        $newTagsStr = implode(', ', $tags ?? []);
        $oldTagsStr = is_array($deal->tags) ? implode(', ', $deal->tags) : '';
        if ($newTagsStr !== $oldTagsStr) $changes[] = "Tags: \"{$oldTagsStr}\" → \"{$newTagsStr}\"";

        // Check assignee change
        $oldAssigneeId = $deal->assigned_to_id;
        $newAssigneeId = $request->assigned_to_id ?: null;

        $deal->update([
            'title' => $request->title,
            'value' => $request->value,
            'status' => $request->status,
            'email_id' => $request->email_id,
            'assigned_to_id' => $request->assigned_to_id,
            'expected_close_at' => $request->expected_close_at,
            'notes' => $request->notes,
            'tags' => $tags,
        ]);

        // Log edit activity
        if (!empty($changes)) {
            DealActivity::create([
                'deal_id' => $deal->id,
                'user_id' => auth()->id(),
                'type' => 'edited',
                'description' => "Updated: " . implode(', ', $changes),
            ]);
        }

        // Log assignment change
        if ($oldAssigneeId != $newAssigneeId) {
            $newAssignee = $newAssigneeId ? \App\Models\User::find($newAssigneeId) : null;
            $oldAssignee = $oldAssigneeId ? \App\Models\User::find($oldAssigneeId) : null;
            DealActivity::create([
                'deal_id' => $deal->id,
                'user_id' => auth()->id(),
                'type' => 'assigned',
                'description' => $newAssignee
                    ? "Assigned to " . $newAssignee->name
                    : "Unassigned (was " . ($oldAssignee->name ?? 'Unknown') . ")",
                'old_value' => $oldAssignee->name ?? null,
                'new_value' => $newAssignee->name ?? null,
            ]);
        }

        // Log status change separately
        if ($request->status && $request->status !== $deal->getOriginal('status')) {
            DealActivity::create([
                'deal_id' => $deal->id,
                'user_id' => auth()->id(),
                'type' => 'status_changed',
                'description' => "Status changed to " . ucfirst($request->status),
                'old_value' => $deal->getOriginal('status'),
                'new_value' => $request->status,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function destroyDeal(Deal $deal)
    {
        $pipeline = $deal->stage->pipeline;
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                abort(403, 'This pipeline is private.');
            }
        }
        if (!$pipeline->canPerformAction('delete_deal')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to delete deals from this pipeline.'], 403);
        }

        // Log before deleting
        DealActivity::create([
            'deal_id' => $deal->id,
            'user_id' => auth()->id(),
            'type' => 'deleted',
            'description' => "Deal \"{$deal->title}\" (₹" . number_format($deal->value) . ") deleted",
        ]);

        $deal->delete();
        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────
    // Deal Activity Timeline
    // ──────────────────────────────────────────────

    public function dealActivities(Deal $deal)
    {
        $activities = $deal->activities()
            ->with('performer:id,name')
            ->latest()
            ->take(50)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'type' => $a->type,
                    'description' => $a->description,
                    'old_value' => $a->old_value,
                    'new_value' => $a->new_value,
                    'performer' => $a->performer->name ?? 'System',
                    'time_ago' => $a->created_at->diffForHumans(),
                    'created_at' => $a->created_at->format('M d, Y h:i A'),
                ];
            });

        return response()->json(['success' => true, 'activities' => $activities]);
    }

    // ──────────────────────────────────────────────
    // Pipeline Analytics
    // ──────────────────────────────────────────────

    public function analytics(Pipeline $pipeline)
    {
        $pipeline->load(['stages.deals']);
        $allDeals = $pipeline->deals;

        // Win/Loss ratio
        $wonCount = $allDeals->where('status', 'won')->count();
        $lostCount = $allDeals->where('status', 'lost')->count();
        $openCount = $allDeals->where('status', 'open')->count();
        $totalDeals = $allDeals->count();

        // Stage distribution
        $stageDistribution = $pipeline->stages->map(function ($stage) {
            return [
                'name' => $stage->name,
                'color' => $stage->color,
                'deal_count' => $stage->deals->count(),
                'total_value' => (float) $stage->deals->sum('value'),
            ];
        });

        // Monthly forecast (next 6 months of expected close dates)
        $monthlyForecast = $allDeals
            ->where('status', 'open')
            ->whereNotNull('expected_close_at')
            ->groupBy(function ($deal) {
                return $deal->expected_close_at->format('Y-m');
            })
            ->map(function ($deals, $month) {
                return [
                    'month' => $month,
                    'label' => \Carbon\Carbon::parse($month . '-01')->format('M Y'),
                    'count' => $deals->count(),
                    'value' => (float) $deals->sum('value'),
                ];
            })
            ->sortKeys()
            ->values()
            ->take(6);

        // Conversion funnel
        $stages = $pipeline->stages->sortBy('order')->values();
        $conversionFunnel = [];
        for ($i = 0; $i < $stages->count(); $i++) {
            $currentCount = $stages[$i]->deals->count();
            // Count deals that passed through this stage (current + all later stages)
            $passedThrough = 0;
            for ($j = $i; $j < $stages->count(); $j++) {
                $passedThrough += $stages[$j]->deals->count();
            }
            $conversionFunnel[] = [
                'stage' => $stages[$i]->name,
                'color' => $stages[$i]->color,
                'count' => $passedThrough,
                'current' => $currentCount,
            ];
        }

        // Average deal value
        $avgDealValue = $totalDeals > 0 ? (float) $allDeals->avg('value') : 0;

        // Average time to close (won deals only)
        $wonDeals = $allDeals->where('status', 'won');
        $avgTimeToClose = 0;
        if ($wonDeals->count() > 0) {
            $totalDays = $wonDeals->sum(function ($deal) {
                return $deal->created_at->diffInDays($deal->updated_at);
            });
            $avgTimeToClose = round($totalDays / $wonDeals->count(), 1);
        }

        // Win rate
        $closedDeals = $wonCount + $lostCount;
        $winRate = $closedDeals > 0 ? round(($wonCount / $closedDeals) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_deals' => $totalDeals,
                'won_count' => $wonCount,
                'lost_count' => $lostCount,
                'open_count' => $openCount,
                'total_value' => (float) $allDeals->sum('value'),
                'won_value' => (float) $allDeals->where('status', 'won')->sum('value'),
                'lost_value' => (float) $allDeals->where('status', 'lost')->sum('value'),
                'avg_deal_value' => round($avgDealValue),
                'avg_time_to_close' => $avgTimeToClose,
                'win_rate' => $winRate,
                'stage_distribution' => $stageDistribution,
                'monthly_forecast' => $monthlyForecast,
                'conversion_funnel' => $conversionFunnel,
            ],
        ]);
    }

    // ──────────────────────────────────────────────
    // Pipeline Settings & Management
    // ──────────────────────────────────────────────

    public function updateSettings(Request $request, Pipeline $pipeline)
    {
        $this->authorizeOwner($pipeline);

        $request->validate([
            'is_public' => 'required|boolean',
            'team_permissions' => 'nullable|array',
            'monthly_target' => 'nullable|numeric|min:0',
            'rotting_days' => 'nullable|integer|min:1',
        ]);

        $teamPermissions = [
            'add_deal' => !empty($request->input('team_permissions.add_deal')),
            'edit_deal' => !empty($request->input('team_permissions.edit_deal')),
            'delete_deal' => !empty($request->input('team_permissions.delete_deal')),
            'move_deal' => !empty($request->input('team_permissions.move_deal')),
        ];

        $pipeline->update([
            'is_public' => $request->is_public,
            'team_permissions' => $teamPermissions,
            'monthly_target' => $request->monthly_target ?? 0,
            'rotting_days' => $request->rotting_days ?? 14,
        ]);

        return response()->json(['success' => true, 'message' => 'Pipeline settings updated successfully.']);
    }

    public function transferOwnership(Request $request, Pipeline $pipeline)
    {
        $this->authorizeOwner($pipeline);

        $request->validate([
            'new_owner_id' => 'required|exists:users,id',
        ]);

        $pipeline->update([
            'created_by_id' => $request->new_owner_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Pipeline ownership transferred successfully.']);
    }

    public function destroy(Pipeline $pipeline)
    {
        $this->authorizeOwner($pipeline);

        foreach ($pipeline->stages as $stage) {
            $stage->deals()->delete();
            $stage->delete();
        }
        $pipeline->delete();

        return response()->json(['success' => true, 'message' => 'Pipeline deleted successfully.']);
    }

    // ──────────────────────────────────────────────
    // Deal Tasks & Comments (AJAX)
    // ──────────────────────────────────────────────

    public function dealTasks(Deal $deal)
    {
        $pipeline = $deal->stage->pipeline;
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                abort(403, 'This pipeline is private.');
            }
        }

        $tasks = $deal->tasks()->latest()->get()->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                'is_completed' => (bool)$task->is_completed,
            ];
        });

        return response()->json(['success' => true, 'tasks' => $tasks]);
    }

    public function storeDealTask(Request $request, Deal $deal)
    {
        $pipeline = $deal->stage->pipeline;
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                abort(403, 'This pipeline is private.');
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'due_date' => 'nullable|date',
        ]);

        $task = \App\Models\ContactTask::create([
            'email_id' => $deal->email_id,
            'deal_id' => $deal->id,
            'title' => $request->title,
            'due_date' => $request->due_date,
            'is_completed' => false,
            'user_id' => $pipeline->user_id,
        ]);

        // Log Activity
        DealActivity::create([
            'deal_id' => $deal->id,
            'user_id' => auth()->id(),
            'type' => 'edited',
            'description' => "Created task: \"{$task->title}\"",
        ]);

        return response()->json(['success' => true, 'task' => [
            'id' => $task->id,
            'title' => $task->title,
            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
            'is_completed' => (bool)$task->is_completed,
        ]]);
    }

    public function toggleDealTask(\App\Models\ContactTask $task)
    {
        $deal = $task->deal;
        if ($deal) {
            $pipeline = $deal->stage->pipeline;
            if (app()->has('team_user')) {
                $teamUserId = app('team_user')->id;
                if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                    abort(403, 'This pipeline is private.');
                }
            }
        }

        $task->update(['is_completed' => !$task->is_completed]);

        if ($deal) {
            $statusText = $task->is_completed ? 'completed' : 'reopened';
            DealActivity::create([
                'deal_id' => $deal->id,
                'user_id' => auth()->id(),
                'type' => 'edited',
                'description' => "Marked task \"{$task->title}\" as {$statusText}",
            ]);
        }

        return response()->json(['success' => true, 'is_completed' => (bool)$task->is_completed]);
    }

    public function destroyDealTask(\App\Models\ContactTask $task)
    {
        $deal = $task->deal;
        if ($deal) {
            $pipeline = $deal->stage->pipeline;
            if (app()->has('team_user')) {
                $teamUserId = app('team_user')->id;
                if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                    abort(403, 'This pipeline is private.');
                }
            }
        }

        $task->delete();

        if ($deal) {
            DealActivity::create([
                'deal_id' => $deal->id,
                'user_id' => auth()->id(),
                'type' => 'edited',
                'description' => "Deleted task \"{$task->title}\"",
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function storeDealComment(Request $request, Deal $deal)
    {
        $pipeline = $deal->stage->pipeline;
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                abort(403, 'This pipeline is private.');
            }
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $activity = DealActivity::create([
            'deal_id' => $deal->id,
            'user_id' => auth()->id(),
            'type' => 'note_added',
            'description' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'activity' => [
                'id' => $activity->id,
                'type' => $activity->type,
                'description' => $activity->description,
                'performer' => $activity->performer->name ?? 'System',
                'time_ago' => $activity->created_at->diffForHumans(),
                'created_at' => $activity->created_at->format('M d, Y h:i A'),
            ]
        ]);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function authorizeOwner(Pipeline $pipeline): void
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if ($pipeline->created_by_id !== $teamUserId) {
                abort(403, 'Only the pipeline owner can perform this action.');
            }
        } else if (auth()->id() !== $pipeline->created_by_id && auth()->user()->role !== \App\Models\User::ROLE_ADMIN) {
            abort(403, 'Only the pipeline owner or admin can perform this action.');
        }
    }
}
