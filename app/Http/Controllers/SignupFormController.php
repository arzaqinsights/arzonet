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
                $label = is_string($header) && !str_starts_with($header, 'custom_') ? $header : ucwords(str_replace(['custom_', '_'], ['', ' '], $key));
                $fields[] = ['name' => $label, 'key' => $key];
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
        $forms = SignupForm::where('email_list_id', $emailList->id)
            ->withCount(['views', 'submissions as completed_submissions_count' => function ($query) {
                $query->where('is_completed', true);
            }])
            ->latest()
            ->get();

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
            'is_multi_step' => 'nullable|boolean',
            'steps_json' => 'nullable|string',
        ]);

        $tagsArray = [];
        if ($request->filled('tags')) {
            $tagsArray = array_map('trim', array_filter(explode(',', $request->tags)));
        }

        $steps = null;
        if ($request->boolean('is_multi_step') && $request->filled('steps_json')) {
            $steps = json_decode($request->steps_json, true);
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
            'steps' => $steps,
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
            'is_multi_step' => 'nullable|boolean',
            'steps_json' => 'nullable|string',
        ]);

        $tagsArray = [];
        if ($request->filled('tags')) {
            $tagsArray = array_map('trim', array_filter(explode(',', $request->tags)));
        }

        $steps = null;
        if ($request->boolean('is_multi_step') && $request->filled('steps_json')) {
            $steps = json_decode($request->steps_json, true);
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
            'steps' => $steps,
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

    public function analytics(SignupForm $signupForm)
    {
        $emailList = $this->getActiveList();

        if ($signupForm->email_list_id !== $emailList->id) {
            abort(403, 'Unauthorized.');
        }

        // Get views and submissions counts
        $totalViews = \App\Models\FormView::where('signup_form_id', $signupForm->id)->count();
        $uniqueViews = \App\Models\FormView::where('signup_form_id', $signupForm->id)->distinct('session_id')->count();
        
        $totalSubmissions = \App\Models\FormSubmission::where('signup_form_id', $signupForm->id)->where('is_completed', true)->count();
        $abandonedSubmissions = \App\Models\FormSubmission::where('signup_form_id', $signupForm->id)->where('is_completed', false)->count();

        $conversionRate = $uniqueViews > 0 ? round(($totalSubmissions / $uniqueViews) * 100, 1) : 0;

        // Group by day for the last 30 days
        $viewsByDay = \App\Models\FormView::where('signup_form_id', $signupForm->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        $submissionsByDay = \App\Models\FormSubmission::where('signup_form_id', $signupForm->id)
            ->where('is_completed', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        $chartDates = [];
        $chartViews = [];
        $chartSubmissions = [];
        for ($i = 29; $i >= 0; $i--) {
            $dateStr = now()->subDays($i)->format('Y-m-d');
            $chartDates[] = now()->subDays($i)->format('M d');
            $chartViews[] = $viewsByDay[$dateStr] ?? 0;
            $chartSubmissions[] = $submissionsByDay[$dateStr] ?? 0;
        }

        // Referrers breakdown
        $referrers = \App\Models\FormView::where('signup_form_id', $signupForm->id)
            ->selectRaw('referrer, COUNT(*) as count')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $host = $row->referrer ? parse_url($row->referrer, PHP_URL_HOST) : 'Direct / Search';
                return [
                    'url' => $row->referrer ?? 'Direct / Search',
                    'host' => $host ?: 'Direct / Search',
                    'count' => $row->count,
                ];
            });

        // Step Funnel analysis
        $stepsStats = [];
        $steps = $signupForm->steps ?? [];
        if (!empty($steps)) {
            $stepsCount = count($steps);
            for ($i = 0; $i < $stepsCount; $i++) {
                $stepNumber = $i + 1;
                $stepTitle = $steps[$i]['title'] ?? "Step {$stepNumber}";
                
                // Reached Step i
                if ($stepNumber === 1) {
                    $reached = $uniqueViews;
                } else {
                    $reached = $totalSubmissions + \App\Models\FormSubmission::where('signup_form_id', $signupForm->id)
                        ->where('is_completed', false)
                        ->where('abandoned_step', '>=', $stepNumber)
                        ->count();
                }

                // Drop-offs at Step i
                if ($stepNumber < $stepsCount) {
                    // Next step reached
                    $nextStepReached = $totalSubmissions + \App\Models\FormSubmission::where('signup_form_id', $signupForm->id)
                        ->where('is_completed', false)
                        ->where('abandoned_step', '>=', $stepNumber + 1)
                        ->count();
                    $dropoff = $reached - $nextStepReached;
                } else {
                    $dropoff = $reached - $totalSubmissions;
                }

                $stepsStats[] = [
                    'step_number' => $stepNumber,
                    'title' => $stepTitle,
                    'reached' => $reached,
                    'reached_pct' => $uniqueViews > 0 ? round(($reached / $uniqueViews) * 100, 1) : 0,
                    'dropoff' => $dropoff,
                    'dropoff_pct' => $reached > 0 ? round(($dropoff / $reached) * 100, 1) : 0,
                ];
            }
        }

        return view('crm.forms.analytics', compact(
            'signupForm',
            'emailList',
            'totalViews',
            'uniqueViews',
            'totalSubmissions',
            'abandonedSubmissions',
            'conversionRate',
            'chartDates',
            'chartViews',
            'chartSubmissions',
            'referrers',
            'stepsStats'
        ));
    }
}
