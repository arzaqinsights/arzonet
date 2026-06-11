<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\Template;
use App\Models\SubscriptionTopic;
use App\Models\EmailList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowController extends Controller
{
    protected function getActiveWorkspaceId()
    {
        $workspaceId = session('last_opened_list_id');
        if (!$workspaceId) {
            abort(400, 'Please select or create a workspace first.');
        }
        return $workspaceId;
    }

    public function index()
    {
        $workspaceId = $this->getActiveWorkspaceId();
        $workflows = Workflow::where('email_list_id', $workspaceId)
            ->latest()
            ->paginate(15);

        return view('workflows.index', compact('workflows'));
    }

    public function create()
    {
        $workspaceId = $this->getActiveWorkspaceId();
        $templates = Template::where('user_id', Auth::id())->latest()->get();
        $topics = SubscriptionTopic::where('email_list_id', $workspaceId)->get();

        // Get unique tags used in this list
        $tags = \DB::table('emails')
            ->where('email_list_id', $workspaceId)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatMap(function ($item) {
                return is_array($item) ? $item : (json_decode($item, true) ?: []);
            })
            ->unique()
            ->values()
            ->toArray();

        $pipelines = \App\Models\Pipeline::with('stages')->get();

        return view('workflows.create', compact('templates', 'topics', 'tags', 'pipelines'));
    }

    public function store(Request $request)
    {
        $workspaceId = $this->getActiveWorkspaceId();

        $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'trigger_type' => 'required|string|in:list_signup,topic_subscribe,tag_added',
            'trigger_value'=> 'nullable|string|max:255',
            'nodes'        => 'required|json',
        ]);

        $nodesArray = json_decode($request->nodes, true);

        Workflow::create([
            'user_id'       => Auth::id(),
            'email_list_id' => $workspaceId,
            'name'          => $request->name,
            'description'   => $request->description,
            'trigger_type'  => $request->trigger_type,
            'trigger_value' => $request->trigger_value,
            'nodes'         => $nodesArray,
            'is_active'     => $request->has('is_active'),
        ]);

        return redirect()
            ->route('admin.workflows.index')
            ->with('success', 'Workflow created successfully.');
    }

    public function edit(Workflow $workflow)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($workflow->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $templates = Template::where('user_id', Auth::id())->latest()->get();
        $topics = SubscriptionTopic::where('email_list_id', $workspaceId)->get();

        // Get unique tags used in this list
        $tags = \DB::table('emails')
            ->where('email_list_id', $workspaceId)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatMap(function ($item) {
                return is_array($item) ? $item : (json_decode($item, true) ?: []);
            })
            ->unique()
            ->values()
            ->toArray();

        $pipelines = \App\Models\Pipeline::with('stages')->get();

        return view('workflows.edit', compact('workflow', 'templates', 'topics', 'tags', 'pipelines'));
    }

    public function update(Request $request, Workflow $workflow)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($workflow->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'trigger_type' => 'required|string|in:list_signup,topic_subscribe,tag_added',
            'trigger_value'=> 'nullable|string|max:255',
            'nodes'        => 'required|json',
        ]);

        $nodesArray = json_decode($request->nodes, true);

        $workflow->update([
            'name'          => $request->name,
            'description'   => $request->description,
            'trigger_type'  => $request->trigger_type,
            'trigger_value' => $request->trigger_value,
            'nodes'         => $nodesArray,
            'is_active'     => $request->has('is_active'),
        ]);

        return redirect()
            ->route('admin.workflows.index')
            ->with('success', 'Workflow updated successfully.');
    }

    public function destroy(Workflow $workflow)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($workflow->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $workflow->delete();

        return redirect()
            ->route('admin.workflows.index')
            ->with('success', 'Workflow deleted successfully.');
    }
}
