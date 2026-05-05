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
        $query = Email::with(['emailList'])
            ->withCount(['activities as opens_count' => function($q) {
                $q->where('type', 'opened');
            }])
            ->withCount(['activities as clicks_count' => function($q) {
                $q->where('type', 'clicked');
            }]);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('email', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('subscription_status', $request->status);
        }

        $contacts = $query->latest('last_active_at')->paginate(20);

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

        ContactNote::create([
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
}
