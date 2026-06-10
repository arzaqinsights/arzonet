<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use App\Models\Email;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SegmentBuilderController extends Controller
{
    public function index()
    {
        $segments = Segment::latest()->get()->map(function ($segment) {
            $segment->contact_count = $this->countMatchingContacts($segment->rules ?? []);
            return $segment;
        });

        return view('crm.segments.index', compact('segments'));
    }

    public function create()
    {
        $customFields = CustomField::all();
        return view('crm.segments.create', compact('customFields'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'rules' => 'required|array|min:1',
        ]);

        Segment::create([
            'name'        => $request->name,
            'description' => $request->description,
            'rules'       => $request->rules,
        ]);

        return redirect()
            ->route('admin.segments.index')
            ->with('success', 'Segment saved successfully.');
    }

    public function edit(Segment $segment)
    {
        $customFields = CustomField::all();
        return view('crm.segments.edit', compact('segment', 'customFields'));
    }

    public function update(Request $request, Segment $segment)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'rules' => 'required|array|min:1',
        ]);

        $segment->update([
            'name'        => $request->name,
            'description' => $request->description,
            'rules'       => $request->rules,
        ]);

        return redirect()
            ->route('admin.segments.index')
            ->with('success', 'Segment updated successfully.');
    }

    public function show(Segment $segment)
    {
        $query = Email::query();
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
        $count = $this->countMatchingContacts($rules);
        return response()->json(['count' => $count]);
    }

    public function destroy(Segment $segment)
    {
        $segment->delete();
        return redirect()
            ->route('admin.segments.index')
            ->with('success', 'Segment deleted.');
    }

    private function countMatchingContacts(array $rules): int
    {
        if (empty($rules)) return 0;
        $query = Email::query();
        return Segment::applyRulesToQuery($query, $rules)->count();
    }
}
