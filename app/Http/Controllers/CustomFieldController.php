<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomFieldController extends Controller
{
    public function index()
    {
        $fields = CustomField::latest()->get();
        return view('crm.custom-fields.index', compact('fields'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'label'   => 'required|string|max:255',
            'type'    => 'required|in:text,number,date,select',
            'choices' => 'nullable|string',
        ]);

        $name = Str::slug($request->label, '_');

        // Ensure uniqueness per user
        $existing = CustomField::withoutGlobalScopes()->where('name', $name)->where('user_id', auth()->id())->exists();
        if ($existing) {
            return back()->withErrors(['label' => 'A field with this name already exists.']);
        }

        $choices = null;
        if ($request->type === 'select' && $request->choices) {
            $choices = array_map('trim', explode(',', $request->choices));
        }

        CustomField::create([
            'name'    => $name,
            'label'   => $request->label,
            'type'    => $request->type,
            'choices' => $choices,
        ]);

        return redirect()
            ->route('admin.custom-fields.index')
            ->with('success', 'Custom field created successfully.');
    }

    public function destroy(CustomField $customField)
    {
        $customField->delete();
        return redirect()
            ->route('admin.custom-fields.index')
            ->with('success', 'Custom field deleted.');
    }
}
