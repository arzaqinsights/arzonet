<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\ContactActivity;
use App\Models\ContactNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * CRM: Global Contacts Index
     */
    public function index(Request $request)
    {
        $activeWorkspaceId = session('last_opened_list_id');

        $query = Email::with(['emailList'])
            ->when($activeWorkspaceId, function($q) use ($activeWorkspaceId) {
                $q->where('email_list_id', $activeWorkspaceId);
            });

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('subscription_status', $request->status);
        }

        $contacts = $query->latest('last_active_at')->simplePaginate(20);

        return view('contacts.index', compact('contacts'));
    }

    /**
     * CRM: Contact Profile View
     */
    public function show(Email $email)
    {
        $email->load(['emailList', 'activities.campaign', 'notes.user']);

        $stats = [
            'total_sent' => $email->logs()->where('status', 'sent')->count(),
            'total_opens' => $email->activities()->where('type', 'opened')->count(),
            'total_clicks' => $email->activities()->where('type', 'clicked')->count(),
        ];

        return view('contacts.show', compact('email', 'stats'));
    }

    /**
     * CRM: Add Note
     */
    public function addNote(Request $request, Email $email)
    {
        $request->validate(['content' => 'required|string']);

        ContactNote::query()->create([
            'email_id' => $email->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        return back()->with('success', 'Note added to contact profile.');
    }

    /**
     * CRM: Update Tags
     */
    public function updateTags(Request $request, Email $email)
    {
        $tags = array_filter(explode(',', $request->tags));
        $email->update(['tags' => array_map('trim', $tags)]);

        return back()->with('success', 'Tags updated.');
    }
    /**
     * CRM: Update Topics
     */
    public function updateTopics(Request $request, Email $email)
    {
        $topicIds = $request->input('topics', []);

        $email->update([
            'subscribed_topics' => array_map('intval', $topicIds),
            'subscription_status' => count($topicIds) > 0 ? 'subscribed' : 'unsubscribed',
        ]);

        return back()->with('success', 'Subscription topics updated.');
    }
}
