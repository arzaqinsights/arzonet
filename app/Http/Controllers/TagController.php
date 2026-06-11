<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailList;
use App\Models\Email;

class TagController extends Controller
{
    private function getActiveList()
    {
        $listId = session('last_opened_list_id') ?? EmailList::orderBy('id', 'asc')->first()->id ?? 1;
        $emailList = EmailList::findOrFail($listId);

        // Visibility check
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list workspace is private.');
            }
        }

        return $emailList;
    }

    public function index()
    {
        $emailList = $this->getActiveList();
        
        $tagsCollection = Email::where('email_list_id', $emailList->id)
            ->whereNotNull('tags')
            ->pluck('tags');

        $tags = collect($tagsCollection)
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->map(function ($tagName) use ($emailList) {
                $count = Email::where('email_list_id', $emailList->id)
                    ->whereJsonContains('tags', $tagName)
                    ->count();
                return [
                    'name' => $tagName,
                    'contact_count' => $count
                ];
            })
            ->sortByDesc('contact_count')
            ->values()
            ->toArray();

        return view('crm.tags.index', compact('emailList', 'tags'));
    }

    public function rename(Request $request)
    {
        $emailList = $this->getActiveList();

        $request->validate([
            'old_name' => 'required|string',
            'new_name' => 'required|string|max:255',
        ]);

        $oldTag = $request->old_name;
        $newTag = $request->new_name;

        $emails = Email::where('email_list_id', $emailList->id)
            ->whereJsonContains('tags', $oldTag)
            ->get();

        foreach ($emails as $email) {
            $tags = $email->tags ?: [];
            $tags = array_values(array_filter($tags, fn($t) => $t !== $oldTag));
            if (!in_array($newTag, $tags)) {
                $tags[] = $newTag;
            }
            $email->update(['tags' => $tags]);
        }

        return redirect()->back()->with('success', 'Tag renamed successfully.');
    }

    public function merge(Request $request)
    {
        $emailList = $this->getActiveList();

        $request->validate([
            'source_tag' => 'required|string',
            'target_tag' => 'required|string',
        ]);

        $sourceTag = $request->source_tag;
        $targetTag = $request->target_tag;

        $emails = Email::where('email_list_id', $emailList->id)
            ->whereJsonContains('tags', $sourceTag)
            ->get();

        foreach ($emails as $email) {
            $tags = $email->tags ?: [];
            $tags = array_values(array_filter($tags, fn($t) => $t !== $sourceTag));
            if (!in_array($targetTag, $tags)) {
                $tags[] = $targetTag;
            }
            $email->update(['tags' => $tags]);
        }

        return redirect()->back()->with('success', 'Tags merged successfully.');
    }

    public function delete(Request $request)
    {
        $emailList = $this->getActiveList();

        $request->validate([
            'tag' => 'required|string',
        ]);

        $tagToDelete = $request->tag;

        $emails = Email::where('email_list_id', $emailList->id)
            ->whereJsonContains('tags', $tagToDelete)
            ->get();

        foreach ($emails as $email) {
            $tags = $email->tags ?: [];
            $tags = array_values(array_filter($tags, fn($t) => $t !== $tagToDelete));
            $email->update(['tags' => $tags]);
        }

        return redirect()->back()->with('success', 'Tag deleted successfully.');
    }
}
