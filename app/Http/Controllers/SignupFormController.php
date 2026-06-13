<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SignupForm;
use App\Models\EmailList;
use App\Models\SubscriptionTopic;
use Illuminate\Support\Str;

class SignupFormController extends Controller
{
    private function getActiveList()
    {
        $listId = session('last_opened_list_id') ?? EmailList::orderBy('id', 'asc')->first()->id ?? 1;
        $emailList = EmailList::findOrFail($listId);

        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list workspace is private.');
            }
        }

        return $emailList;
    }

    private function getCustomFieldsForList(EmailList $list)
    {
        $fields = [];

        // 1. Pull fields from column_mapping (CSV imports)
        $mapping = $list->column_mapping ?? [];
        foreach ($mapping as $header => $key) {
            if (is_array($key) || is_object($key)) {
                continue;
            }
            if (is_string($key) && str_starts_with($key, 'custom_')) {
                $fields[] = ['name' => ucwords(str_replace(['custom_', '_'], ['', ' '], $key)), 'key' => $key];
            } else if (is_string($header) && str_starts_with($header, 'custom_')) {
                $label = is_string($key) ? $key : ucwords(str_replace(['custom_', '_'], ['', ' '], $header));
                $fields[] = ['name' => $label, 'key' => $header];
            }
        }

        // 2. Also scan meta keys from existing contacts in this list
        //    (handles fields added via signup forms, manual entry, etc.)
        $metaSamples = \App\Models\Email::where('email_list_id', $list->id)
            ->whereNotNull('meta')
            ->where('meta', '!=', '{}')
            ->where('meta', '!=', 'null')
            ->limit(200)
            ->pluck('meta');

        foreach ($metaSamples as $meta) {
            if (!is_array($meta)) continue;
            foreach (array_keys($meta) as $metaKey) {
                if (!is_string($metaKey) || !str_starts_with($metaKey, 'custom_')) continue;
                if (!isset($fields[$metaKey])) {
                    $label = ucwords(str_replace(['custom_', '_'], ['', ' '], $metaKey));
                    $fields[$metaKey] = ['name' => $label, 'key' => $metaKey];
                }
            }
        }

        // Deduplicate by key
        $unique = [];
        foreach ($fields as $f) {
            if (is_array($f) && isset($f['key'])) {
                $unique[$f['key']] = $f;
            }
        }
        return array_values($unique);
    }

    public function index()
    {
        $emailList = $this->getActiveList();
        $forms = SignupForm::where('email_list_id', $emailList->id)->latest()->get();

        return view('crm.forms.index', compact('emailList', 'forms'));
    }

    public function create()
    {
        $emailList = $this->getActiveList();
        $topics = SubscriptionTopic::where('email_list_id', $emailList->id)->get();
        $customFields = $this->getCustomFieldsForList($emailList);

        return view('crm.forms.create', compact('emailList', 'topics', 'customFields'));
    }

    public function store(Request $request)
    {
        $emailList = $this->getActiveList();

        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'button_text' => 'required|string|max:100',
            'success_message' => 'nullable|string',
            'double_opt_in' => 'boolean',
            'allow_topic_selection' => 'boolean',
            'theme_color' => 'required|string|max:7',
            'subscribed_topics' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'tags' => 'nullable|string',
        ]);

        $tagsArray = [];
        if ($request->filled('tags')) {
            $tagsArray = array_map('trim', array_filter(explode(',', $request->tags)));
        }

        SignupForm::create([
            'email_list_id' => $emailList->id,
            'user_id' => auth()->id(),
            'name' => $request->name,
            'token' => Str::random(32),
            'title' => $request->title,
            'description' => $request->description,
            'button_text' => $request->button_text,
            'success_message' => $request->success_message ?? 'Thank you for subscribing!',
            'double_opt_in' => $request->boolean('double_opt_in'),
            'allow_topic_selection' => $request->boolean('allow_topic_selection'),
            'theme_color' => $request->theme_color,
            'subscribed_topics' => $request->subscribed_topics ?? [],
            'custom_fields' => $request->custom_fields ?? [],
            'tags' => $tagsArray,
        ]);

        return redirect()->route('admin.signup-forms.index')->with('success', 'Signup form created successfully.');
    }

    public function edit(SignupForm $signupForm)
    {
        $emailList = $this->getActiveList();

        if ($signupForm->email_list_id !== $emailList->id) {
            abort(403, 'Unauthorized.');
        }

        $topics = SubscriptionTopic::where('email_list_id', $emailList->id)->get();
        $customFields = $this->getCustomFieldsForList($emailList);

        return view('crm.forms.edit', compact('signupForm', 'emailList', 'topics', 'customFields'));
    }

    public function update(Request $request, SignupForm $signupForm)
    {
        $emailList = $this->getActiveList();

        if ($signupForm->email_list_id !== $emailList->id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'button_text' => 'required|string|max:100',
            'success_message' => 'nullable|string',
            'double_opt_in' => 'boolean',
            'allow_topic_selection' => 'boolean',
            'theme_color' => 'required|string|max:7',
            'subscribed_topics' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'tags' => 'nullable|string',
        ]);

        $tagsArray = [];
        if ($request->filled('tags')) {
            $tagsArray = array_map('trim', array_filter(explode(',', $request->tags)));
        }

        $signupForm->update([
            'name' => $request->name,
            'title' => $request->title,
            'description' => $request->description,
            'button_text' => $request->button_text,
            'success_message' => $request->success_message ?? 'Thank you for subscribing!',
            'double_opt_in' => $request->boolean('double_opt_in'),
            'allow_topic_selection' => $request->boolean('allow_topic_selection'),
            'theme_color' => $request->theme_color,
            'subscribed_topics' => $request->subscribed_topics ?? [],
            'custom_fields' => $request->custom_fields ?? [],
            'tags' => $tagsArray,
        ]);

        return redirect()->route('admin.signup-forms.index')->with('success', 'Signup form updated successfully.');
    }

    public function destroy(SignupForm $signupForm)
    {
        $emailList = $this->getActiveList();

        if ($signupForm->email_list_id !== $emailList->id) {
            abort(403, 'Unauthorized.');
        }

        $signupForm->delete();

        return redirect()->route('admin.signup-forms.index')->with('success', 'Signup form deleted successfully.');
    }
}
