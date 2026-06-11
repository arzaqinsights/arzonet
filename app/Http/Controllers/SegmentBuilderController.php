<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use App\Models\Email;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SegmentBuilderController extends Controller
{
    private function getActiveListId()
    {
        return session('last_opened_list_id') ?? \App\Models\EmailList::orderBy('id', 'asc')->first()->id ?? 1;
    }

    private function getCustomFieldsForActiveList($listId)
    {
        $list = \App\Models\EmailList::find($listId);

        if (!$list) {
            return [];
        }

        $fields = [];
        $mapping = $list->column_mapping ?? [];

        foreach ($mapping as $header => $key) {

            // Skip invalid values
            if (is_array($key) || is_object($key)) {
                continue;
            }

            if (is_string($key) && str_starts_with($key, 'custom_')) {

                $fields[] = (object) [
                    'name' => $header,
                    'key' => $key
                ];

            } elseif (is_string($header) && str_starts_with($header, 'custom_')) {

                $fields[] = (object) [
                    'name' => $key,
                    'key' => $header
                ];
            }
        }

        $unique = [];

        foreach ($fields as $f) {
            $unique[$f->key] = $f;
        }

        return array_values($unique);
    }

    public function index()
    {
        $listId = $this->getActiveListId();
        $segments = Segment::where('email_list_id', $listId)->latest()->get()->map(function ($segment) use ($listId) {
            $segment->contact_count = $this->countMatchingContacts($segment->rules ?? [], $listId);
            return $segment;
        });

        return view('crm.segments.index', compact('segments'));
    }

    public function create()
    {
        $listId = $this->getActiveListId();
        $customFields = collect($this->getCustomFieldsForActiveList($listId));
        return view('crm.segments.create', compact('customFields'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rules' => 'required|array|min:1',
        ]);

        $listId = $this->getActiveListId();

        Segment::create([
            'user_id' => auth()->id(),
            'email_list_id' => $listId,
            'name' => $request->name,
            'description' => $request->description,
            'rules' => $request->rules,
        ]);

        return redirect()
            ->route('admin.segments.index')
            ->with('success', 'Segment saved successfully.');
    }

    public function edit(Segment $segment)
    {
        $listId = $segment->email_list_id ?? $this->getActiveListId();
        $customFields = collect($this->getCustomFieldsForActiveList($listId));
        return view('crm.segments.edit', compact('segment', 'customFields'));
    }

    public function update(Request $request, Segment $segment)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rules' => 'required|array|min:1',
        ]);

        $segment->update([
            'name' => $request->name,
            'description' => $request->description,
            'rules' => $request->rules,
        ]);

        return redirect()
            ->route('admin.segments.index')
            ->with('success', 'Segment updated successfully.');
    }

    public function show(Segment $segment)
    {
        $listId = $segment->email_list_id ?? $this->getActiveListId();
        $query = Email::where('email_list_id', $listId);
        $query = Segment::applyRulesToQuery($query, $segment->rules ?? []);
        $contacts = $query->paginate(50);

        return view('crm.segments.show', compact('segment', 'contacts'));
    }

    /**
     * AJAX: Live count preview as rules change.
     */
    public function preview(Request $request)
    {
        $rules = $request->input('rules', []);
        $listId = $this->getActiveListId();
        $count = $this->countMatchingContacts($rules, $listId);
        return response()->json(['count' => $count]);
    }

    public function destroy(Segment $segment)
    {
        $segment->delete();
        return redirect()
            ->route('admin.segments.index')
            ->with('success', 'Segment deleted.');
    }

    private function countMatchingContacts(array $rules, $listId): int
    {
        if (empty($rules))
            return 0;
        $query = Email::where('email_list_id', $listId);
        return Segment::applyRulesToQuery($query, $rules)->count();
    }
}
