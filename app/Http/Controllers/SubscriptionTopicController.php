<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionTopicController extends Controller
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
        
        $topicsCount = SubscriptionTopic::where('email_list_id', $workspaceId)->count();
        if ($topicsCount === 0) {
            $emailList = \App\Models\EmailList::find($workspaceId);
            if ($emailList) {
                SubscriptionTopic::seedDefaultsFor($workspaceId, $emailList->user_id);
            }
        }

        $topics = SubscriptionTopic::where('email_list_id', $workspaceId)
            ->latest()
            ->paginate(15);

        return view('subscription-topics.index', compact('topics'));
    }

    public function create()
    {
        return view('subscription-topics.create');
    }

    public function store(Request $request)
    {
        $workspaceId = $this->getActiveWorkspaceId();

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        SubscriptionTopic::create([
            'user_id'       => Auth::id(),
            'email_list_id' => $workspaceId,
            'name'          => $request->name,
            'description'   => $request->description,
        ]);

        return redirect()
            ->route('admin.subscription-topics.index')
            ->with('success', 'Subscription topic created successfully.');
    }

    public function edit(SubscriptionTopic $subscriptionTopic)
    {
        $workspaceId = $this->getActiveWorkspaceId();

        if ($subscriptionTopic->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        return view('subscription-topics.edit', compact('subscriptionTopic'));
    }

    public function update(Request $request, SubscriptionTopic $subscriptionTopic)
    {
        $workspaceId = $this->getActiveWorkspaceId();

        if ($subscriptionTopic->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $subscriptionTopic->update($request->only('name', 'description'));

        return redirect()
            ->route('admin.subscription-topics.index')
            ->with('success', 'Subscription topic updated successfully.');
    }

    public function destroy(SubscriptionTopic $subscriptionTopic)
    {
        $workspaceId = $this->getActiveWorkspaceId();

        if ($subscriptionTopic->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $subscriptionTopic->delete();

        return redirect()
            ->route('admin.subscription-topics.index')
            ->with('success', 'Subscription topic deleted successfully.');
    }
}
