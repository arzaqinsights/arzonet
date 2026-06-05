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

    public function show(Segment $segment)
    {
        $contacts = $this->getMatchingContacts($segment->rules ?? [])->paginate(50);
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

    /**
     * Build the query from rules.
     */
    private function getMatchingContacts(array $rules)
    {
        $query = Email::query();

        foreach ($rules as $rule) {
            $field    = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? null;
            $value    = $rule['value'] ?? null;

            if (!$field || !$operator) continue;

            // Standard columns
            $standardFields = ['name', 'email', 'engagement_score'];

            if (in_array($field, $standardFields)) {
                $dbField = $field;
                match ($operator) {
                    'equals'       => $query->where($dbField, '=', $value),
                    'not_equals'   => $query->where($dbField, '!=', $value),
                    'contains'     => $query->where($dbField, 'LIKE', "%{$value}%"),
                    'greater_than' => $query->where($dbField, '>', $value),
                    'less_than'    => $query->where($dbField, '<', $value),
                    default        => null,
                };
            } else {
                // Custom field — stored in meta JSON
                $jsonPath = "$.{$field}";
                match ($operator) {
                    'equals'       => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) = ?", [$jsonPath, $value]),
                    'not_equals'   => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) != ?", [$jsonPath, $value]),
                    'contains'     => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) LIKE ?", [$jsonPath, "%{$value}%"]),
                    'greater_than' => $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) AS DECIMAL) > ?", [$jsonPath, $value]),
                    'less_than'    => $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) AS DECIMAL) < ?", [$jsonPath, $value]),
                    default        => null,
                };
            }
        }

        return $query;
    }

    private function countMatchingContacts(array $rules): int
    {
        if (empty($rules)) return 0;
        return $this->getMatchingContacts($rules)->count();
    }
}
