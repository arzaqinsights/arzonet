<?php

namespace App\Http\Controllers;

use App\Exports\ContactsExport;
use App\Models\EmailList;
use App\Models\Email;
use App\Services\FileParserService;
use App\Jobs\ProcessEmailListJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\EmailValidationService;

class EmailListController extends Controller
{
    public function index()
    {
        $lists = EmailList::latest()->paginate(15);

        $globalStats = [
            'total' => \App\Models\Email::count(),
            'subscribed' => \App\Models\Email::where('subscription_status', 'subscribed')->count(),
            'unsubscribed' => \App\Models\Email::whereNotNull('unsubscribed_at')->count(),
            'bounced' => \App\Models\EmailLog::where('status', 'bounced')->count(),
            'invalid' => \App\Models\Email::where('status', 'invalid')->count(),
            'duplicate' => \App\Models\Email::where('status', 'duplicate')->count(),
        ];

        return view('email-lists.index', compact('lists', 'globalStats'));
    }

    public function create()
    {
        return view('email-lists.create');
    }

    /**
     * Store a new email list.
     * FLOW: Upload -> Store File -> Return Mapping Preview
     */
    public function store(Request $request, FileParserService $parser)
    {
        $request->validate([
            'import_type' => 'required|in:upload,manual,paste',
        ]);

        $listName = $request->name ?? 'Audience List - ' . now()->format('Y-m-d h:i A');

        $emailList = EmailList::create([
            'name' => $listName,
            'signup_source' => $request->signup_source ?? 'Direct Import',
            'status' => 'pending',
        ]);

        // 1. Handling File Upload
        if ($request->import_type === 'upload') {
            $request->validate([
                'file' => 'required|file|max:' . config('emailplatform.upload.max_file_size', 10240) . '|mimes:csv,xlsx,txt',
            ]);

            $file = $request->file('file');
            $path = $file->store('email-lists', 'local');

            $emailList->update([
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
            ]);

            return $this->showMappingView($emailList, $parser, $file);
        }

        // 2. Handling Bulk Paste (ASYNC REFACTOR)
        if ($request->import_type === 'paste') {
            $request->validate(['emails_text' => 'required|string']);

            // Convert paste to a temporary CSV file for async processing
            $filename = 'paste_' . Str::random(10) . '.csv';
            $path = 'email-lists/' . $filename;

            // Create CSV content with a header
            $content = "email\n" . $request->emails_text;
            Storage::disk('local')->put($path, $content);

            $emailList->update([
                'file_path' => $path,
                'original_filename' => 'bulk_paste.csv',
                'signup_source' => $request->signup_source ?? 'Bulk Paste',
                'column_mapping' => ['email' => 'email', '_settings' => ['skip_dns' => false]],
                'status' => 'processing'
            ]);

            ProcessEmailListJob::dispatch($emailList->id);

            return redirect()->route('admin.email-lists.show', $emailList)->with('success', 'Bulk import started in background.');
        }

        // 3. Handling Manual Add
        if ($request->import_type === 'manual') {
            $request->validate([
                'manual_email' => 'required|email'
            ]);

            $emailList->update([
                'status' => 'completed',
            ]);

            Email::create([
                'email_list_id' => $emailList->id,
                'email' => $request->manual_email,
                'name' => $request->manual_name,
                'status' => 'valid',
                'subscription_status' => 'subscribed'
            ]);

            return redirect()->route('admin.email-lists.show', $emailList)->with('success', 'Contact added successfully.');
        }
    }

    public function updateName(Request $request, EmailList $emailList)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $emailList->update(['name' => $request->name]);

        return response()->json(['success' => true, 'name' => $emailList->name]);
    }

    protected function showMappingView(EmailList $emailList, FileParserService $parser, $file)
    {
        // Preview only - uses streaming to avoid OOM even here
        $parsed = $parser->parse($file);
        $headers = $parsed['headers'];
        $sampleRows = array_slice($parsed['rows'], 0, 5);
        $suggestedEmail = $parser->autoDetectEmailColumn($headers, $sampleRows);
        $suggestedName = $parser->autoDetectNameColumn($headers);
        $autoSuggestions = $parser->autoDetectMappings($headers, $sampleRows);

        return view('email-lists.mapping', compact('emailList', 'headers', 'sampleRows', 'suggestedEmail', 'suggestedName', 'autoSuggestions'));
    }

    public function storeMapping(Request $request, int $id)
    {
        $request->validate([
            'mapping' => 'required|array',
        ]);

        $emailList = EmailList::findOrFail($id);
        $rawMapping = $request->input('mapping', []);
        $finalMapping = [];

        foreach ($rawMapping as $excelColumn => $systemField) {
            if (!empty($systemField)) {
                $finalMapping[$systemField] = $excelColumn;
            }
        }

        if (!isset($finalMapping['email'])) {
            return back()->withErrors(['mapping' => 'You must map the Email Address column.']);
        }

        $finalMapping['_settings'] = ['skip_dns' => false];

        $emailList->update([
            'column_mapping' => $finalMapping,
            'status' => 'processing'
        ]);

        $log = \App\Models\ActivityLog::create([
            'email_list_id' => $emailList->id,
            'user_id' => auth()->id(),
            'type' => 'import',
            'details' => [
                'status' => 'started',
                'source' => $emailList->original_filename,
                'tags' => $emailList->tags,
                'processed' => 0,
                'valid' => 0,
                'invalid' => 0,
                'duplicate' => 0
            ]
        ]);

        ProcessEmailListJob::dispatch($emailList->id, $log->id);

        return redirect()
            ->route('admin.email-lists.show', $emailList)
            ->with('success', 'CRM Import started. Contacts will appear shortly.');
    }

    public function show(EmailList $emailList)
    {
        $emailList->recalculateStats();

        $stats = [
            'total' => $emailList->total_records,
            'valid' => $emailList->valid_count,
            'invalid' => $emailList->invalid_count,
            'duplicate' => $emailList->duplicate_count,
            'subscribed' => $emailList->emails()->where('is_archived', false)->where('subscription_status', 'subscribed')->count(),
            'unsubscribed' => $emailList->emails()->where('is_archived', false)->where('subscription_status', 'unsubscribed')->count(),
            'bounced' => $emailList->emails()->where('is_archived', false)->where('subscription_status', 'bounced')->count(),
            'complaints' => $emailList->emails()->where('is_archived', false)->where('email_status', 'complaint')->count(),
            'archived' => $emailList->emails()->where('is_archived', true)->count(),
            // Health specific
            'risky' => $emailList->emails()->where('is_archived', false)->where('email_status', 'risky')->count(),
            'disposable' => $emailList->emails()->where('is_archived', false)->where('is_disposable', true)->count(),
            'role_based' => $emailList->emails()->where('is_archived', false)->where('is_role_based', true)->count(),
            'suspicious' => $emailList->emails()->where('is_archived', false)->where('email_status', 'suspicious')->count(),
            'hard_bounce' => $emailList->emails()->where('is_archived', false)->where('email_status', 'hard_bounce')->count(),
            'soft_bounce' => $emailList->emails()->where('is_archived', false)->where('email_status', 'soft_bounce')->count(),
        ];

        // Match Alpine.js default filter: Active Only (is_archived = false)
        $emails = $emailList->emails()->where('is_archived', false)->orderBy('created_at', 'desc')->paginate(50);

        $segments = $emailList->emails()->whereNotNull('segment_name')->distinct()->pluck('segment_name');
        $tags = $emailList->emails()->whereNotNull('tags')->distinct()->pluck('tags');
        $sources = $emailList->emails()->whereNotNull('signup_source')->distinct()->pluck('signup_source');

        return view('email-lists.show', compact('emailList', 'stats', 'emails', 'segments', 'tags', 'sources'));
    }

    public function filterEmails(Request $request, EmailList $emailList)
    {
        $query = $emailList->emails()->orderBy('created_at', 'desc');

        // Health filter
        if ($request->status && $request->status !== 'all') {
            if (in_array($request->status, ['risky', 'role_based', 'disposable', 'suspicious'])) {
                if ($request->status === 'role_based') $query->where('is_role_based', true);
                elseif ($request->status === 'disposable') $query->where('is_disposable', true);
                else $query->where('email_status', $request->status);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Subscription status filter
        if ($request->subscription && $request->subscription !== 'all') {
            if (in_array($request->subscription, ['hard_bounce', 'soft_bounce', 'complaint'])) {
                $query->where('email_status', $request->subscription);
            } else {
                $query->where('subscription_status', $request->subscription);
            }
        }

        // Archive filter
        if ($request->archived === 'yes') {
            $query->where('is_archived', true);
        } elseif ($request->archived === 'no') {
            $query->where('is_archived', false);
        }

        // Segment filter
        if ($request->segment && $request->segment !== 'all') {
            $query->where('segment_name', $request->segment);
        }

        // Source filter
        if ($request->source && $request->source !== 'all') {
            $query->where('signup_source', $request->source);
        }

        // Tag filter
        if ($request->tag && $request->tag !== 'all') {
            $query->where('tags', 'like', "%{$request->tag}%");
        }

        // Targeted or Global Search
        if ($request->search) {
            $search = $request->search;
            $field = $request->search_field ?? 'all';

            $query->where(function ($q) use ($search, $field) {
                if ($field === 'email') {
                    $q->where('email', 'like', "%{$search}%");
                } elseif ($field === 'name') {
                    $q->where('name', 'like', "%{$search}%");
                } elseif ($field === 'segment') {
                    $q->where('segment_name', 'like', "%{$search}%");
                } elseif ($field === 'tag') {
                    $q->where('tags', 'like', "%{$search}%");
                } elseif ($field === 'source') {
                    $q->where('signup_source', 'like', "%{$search}%");
                } elseif ($field === 'all') {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('segment_name', 'like', "%{$search}%")
                        ->orWhere('signup_source', 'like', "%{$search}%")
                        ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '\$'))) LIKE ?", ["%" . strtolower($search) . "%"]);
                } else {
                    // Search in meta JSON for specific keys (city, company, etc)
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}'))) LIKE ?", ["%" . strtolower($search) . "%"]);
                }
            });
        }

        // 1. Calculate stats for the FULL LIST (ignoring current filters)
        $globalStats = [
            'valid' => $emailList->emails()->where('status', 'valid')->count(),
            'invalid' => $emailList->emails()->where('status', 'invalid')->count(),
            'duplicate' => $emailList->emails()->where('status', 'duplicate')->count(),
            'subscribed' => $emailList->emails()->where('is_archived', false)->where('status', 'valid')->where('subscription_status', 'subscribed')->count(),
            'segment' => ($request->segment && $request->segment !== 'all') ? $emailList->emails()->where('segment_name', $request->segment)->count() : 0,
            'tag' => ($request->tag && $request->tag !== 'all') ? $emailList->emails()->where('tags', 'like', "%{$request->tag}%")->count() : 0,
            'source' => ($request->source && $request->source !== 'all') ? $emailList->emails()->where('signup_source', $request->source)->count() : 0,
        ];

        // 2. Calculate stats for the CURRENT filtered set
        $statsQuery = clone $query;
        $dynamicStats = [
            'total' => $statsQuery->count(),
            'valid' => (clone $statsQuery)->where('status', 'valid')->count(),
            'invalid' => (clone $statsQuery)->where('status', 'invalid')->count(),
            'duplicate' => (clone $statsQuery)->where('status', 'duplicate')->count(),
            'subscribed' => (clone $statsQuery)->where('subscription_status', 'subscribed')->count(),
            'unsubscribed' => (clone $statsQuery)->where('subscription_status', 'unsubscribed')->count(),
            'bounced' => (clone $statsQuery)->where('subscription_status', 'bounced')->count(),
        ];

        $emails = $query->paginate(50);
        return response()->json([
            'html' => view('email-lists.partials.email-table-rows', ['emails' => $emails, 'emailList' => $emailList])->render(),
            'links' => $emails->links()->toHtml(),
            'stats' => $dynamicStats,
            'global_stats' => $globalStats
        ]);
    }

    public function exportContacts(Request $request, EmailList $emailList)
    {
        $query = $emailList->emails()->orderBy('created_at', 'desc');

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->subscription && $request->subscription !== 'all') {
            $query->where('subscription_status', $request->subscription);
        }
        if ($request->segment && $request->segment !== 'all') {
            $query->where('segment_name', $request->segment);
        }
        if ($request->source && $request->source !== 'all') {
            $query->where('signup_source', $request->source);
        }
        if ($request->tag && $request->tag !== 'all') {
            $query->where('tags', 'like', "%{$request->tag}%");
        }
        if ($request->search) {
            $search = $request->search;
            $field = $request->search_field ?? 'all';

            $query->where(function ($q) use ($search, $field) {
                if ($field === 'email') {
                    $q->where('email', 'like', "%{$search}%");
                } elseif ($field === 'name') {
                    $q->where('name', 'like', "%{$search}%");
                } elseif ($field === 'segment') {
                    $q->where('segment_name', 'like', "%{$search}%");
                } elseif ($field === 'tag') {
                    $q->where('tags', 'like', "%{$search}%");
                } elseif ($field === 'source') {
                    $q->where('signup_source', 'like', "%{$search}%");
                } elseif ($field === 'all') {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('segment_name', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%")
                        ->orWhere('signup_source', 'like', "%{$search}%");
                } else {
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}'))) LIKE ?", ["%" . strtolower($search) . "%"]);
                }
            });
        }

        // Determine extra CRM fields from mapping
        $mapping = $emailList->column_mapping ?? [];
        $extraFields = [];
        foreach (['company', 'job_title', 'phone', 'city', 'state', 'zip', 'country', 'address', 'website'] as $f) {
            if (isset($mapping[$f]))
                $extraFields[] = $f;
        }
        foreach ($mapping as $key => $val) {
            if (str_starts_with($key, 'custom_'))
                $extraFields[] = $key;
        }

        $ext = $request->input('format') === 'csv' ? 'csv' : 'xlsx';
        $defaultFilename = Str::slug($emailList->name) . '_contacts_' . now()->format('Ymd_His');
        $filename = ($request->input('filename') ?: $defaultFilename) . '.' . $ext;

        \App\Models\ActivityLog::create([
            'email_list_id' => $emailList->id,
            'user_id' => auth()->id(),
            'type' => 'export',
            'details' => [
                'filename' => $filename,
                'format' => $ext,
                'filters' => $request->only(['status', 'search', 'segment', 'tag', 'source', 'subscription'])
            ]
        ]);

        return Excel::download(new ContactsExport($query, $extraFields), $filename);
    }

    public function destroyEmail(EmailList $emailList, int $emailId)
    {
        $email = $emailList->emails()->findOrFail($emailId);
        $status = $email->status;
        $is_archived = $email->is_archived;
        $email->delete();

        if (!$is_archived) {
            $emailList->decrement('total_records');
            match ($status) {
                'valid' => $emailList->decrement('valid_count'),
                'invalid' => $emailList->decrement('invalid_count'),
                'duplicate' => $emailList->decrement('duplicate_count'),
                default => null,
            };
        }
        return response()->json(['success' => true]);
    }

    public function destroy(EmailList $emailList)
    {
        if ($emailList->file_path)
            Storage::disk('local')->delete($emailList->file_path);
        $emailList->delete();
        return redirect()->route('admin.email-lists.index')->with('success', 'Email list deleted successfully.');
    }

    public function checkStatus(EmailList $emailList)
    {
        $data = [
            'status' => $emailList->status,
            'total_records' => $emailList->total_records,
            'valid_count' => $emailList->valid_count,
            'invalid_count' => $emailList->invalid_count,
            'duplicate_count' => $emailList->duplicate_count,
            'subscribed_count' => $emailList->emails()->where('is_archived', false)->where('subscription_status', 'subscribed')->count(),
            'unsubscribed_count' => $emailList->emails()->where('is_archived', false)->where('subscription_status', 'unsubscribed')->count(),
            'bounced_count' => $emailList->emails()->where('is_archived', false)->where('subscription_status', 'bounced')->count(),
            'hard_bounce_count' => $emailList->emails()->where('is_archived', false)->where('email_status', 'hard_bounce')->count(),
            'soft_bounce_count' => $emailList->emails()->where('is_archived', false)->where('email_status', 'soft_bounce')->count(),
            'complaint_count' => $emailList->emails()->where('is_archived', false)->where('email_status', 'complaint')->count(),
            'risky_count' => $emailList->emails()->where('is_archived', false)->where('email_status', 'risky')->count(),
            'disposable_count' => $emailList->emails()->where('is_archived', false)->where('is_disposable', true)->count(),
            'role_based_count' => $emailList->emails()->where('is_archived', false)->where('is_role_based', true)->count(),
            'suspicious_count' => $emailList->emails()->where('is_archived', false)->where('email_status', 'suspicious')->count(),
            'archived_count' => $emailList->emails()->where('is_archived', true)->count(),
        ];

        // Check for active batch progress
        $latestLog = $emailList->activityLogs()->where('type', 'import')->where('details->status', 'started')->first();
        if ($latestLog && $latestLog->batch_id) {
            $batch = Bus::findBatch($latestLog->batch_id);
            if ($batch) {
                $data['import_progress'] = $batch->progress();
                $data['import_details'] = $latestLog->details;
            }
        }

        return response()->json($data);
    }

    public function getEmail(EmailList $emailList, int $emailId)
    {
        return response()->json($emailList->emails()->findOrFail($emailId));
    }

    public function updateEmail(Request $request, EmailList $emailList, int $emailId)
    {
        $email = $emailList->emails()->findOrFail($emailId);
        $request->validate(['email' => 'required|email', 'name' => 'nullable|string|max:255']);
        $data = $request->only('email', 'name', 'segment_name', 'signup_source', 'subscription_status');
        if ($request->has('meta'))
            $data['meta'] = array_merge($email->meta ?? [], $request->meta);
        $email->update($data);
        return response()->json(['success' => true]);
    }

    public function addContact(Request $request, EmailList $emailList)
    {
        $request->validate(['email' => 'required|email', 'name' => 'nullable|string|max:255']);

        // Check for permanent deletion record in THIS list
        $banned = $emailList->emails()->where('email', $request->email)->where('status', 'permanent_delete')->exists();
        if ($banned) {
            return response()->json(['success' => false, 'message' => 'This email has been permanently banished from this list.'], 422);
        }

        $existing = $emailList->emails()->where('email', $request->email)->first();

        if ($existing) {
            if ($existing->is_archived) {
                $existing->update([
                    'is_archived' => false,
                    'archived_at' => null,
                    'name' => $request->name ?? $existing->name,
                    'status' => 'valid',
                    'subscription_status' => 'subscribed',
                    'signup_source' => $request->signup_source ?? $existing->signup_source,
                ]);
                return response()->json(['success' => true, 'message' => 'Archived contact restored and updated.']);
            }

            // If exists and not archived, create a duplicate entry as usual for tracking
            $email = $emailList->emails()->create([
                'email' => $request->email,
                'name' => $request->name,
                'segment_name' => $request->segment_name,
                'tags' => $request->tags,
                'signup_source' => $request->signup_source ?? 'Manual',
                'status' => 'duplicate',
                'subscription_status' => 'subscribed',
                'is_archived' => false
            ]);
        } else {
            $email = $emailList->emails()->create([
                'email' => $request->email,
                'name' => $request->name,
                'segment_name' => $request->segment_name,
                'tags' => $request->tags,
                'signup_source' => $request->signup_source ?? 'Manual',
                'status' => 'valid',
                'subscription_status' => 'subscribed',
                'is_archived' => false
            ]);
        }
        $emailList->increment('total_records');
        if ($existing) {
            // If it was already there (archived or duplicate), we technically restored/added a valid status
            $emailList->increment('valid_count');
        } else {
            $emailList->increment('valid_count');
        }
        return response()->json(['success' => true]);
    }

    public function scrubList(EmailList $emailList)
    {
        // This is a high-performance, atomic SQL approach.
        // It marks everything as a duplicate that isn't the "chosen" record for that email.

        DB::transaction(function () use ($emailList, &$affected) {
            $listId = $emailList->id;

            // Step 1: Identify the "Best ID" to keep for every unique email.
            // We prioritize status='valid' first, then lowest ID.
            $bestIdsQuery = DB::table('emails')
                ->where('email_list_id', $listId)
                ->whereNull('deleted_at')
                ->select(DB::raw('MIN(id) as id'))
                ->groupBy(DB::raw('TRIM(LOWER(email))'))
                ->orderByRaw("FIELD(status, 'valid', 'invalid', 'duplicate') ASC");

            // Note: Since MIN(id) doesn't respect the GROUP BY's internal order in standard SQL,
            // we'll use a more robust two-pass logic or a join.

            // Pass A: Find all unique scrubbed emails
            $uniqueEmails = DB::table('emails')
                ->where('email_list_id', $listId)
                ->select(DB::raw('DISTINCT TRIM(LOWER(email)) as email_key'))
                ->pluck('email_key');

            $affected = 0;

            // To prevent memory issues with massive lists, we process in chunks if needed,
            // but for 2k-10k records, a direct update is faster.

            // Step 1: Identify the "Best ID" to keep for every unique email.
            // We prioritize:
            // 1. status='valid'
            // 2. Data Richness (more fields filled)
            // 3. Lowest ID (oldest) as tie-breaker
            DB::statement("CREATE TEMPORARY TABLE temp_best_ids AS 
                SELECT id FROM (
                    SELECT id, ROW_NUMBER() OVER (
                        PARTITION BY TRIM(LOWER(email)) 
                        ORDER BY 
                            -- Priority 1: Keep 'valid' status if exists
                            FIELD(status, 'valid', 'invalid', 'duplicate') ASC,
                            -- Priority 2: Data Richness (Score based on non-empty fields)
                            (
                                (CASE WHEN name IS NOT NULL AND name != '' THEN 2 ELSE 0 END) +
                                (CASE WHEN segment_name IS NOT NULL AND segment_name != '' THEN 1 ELSE 0 END) +
                                (CASE WHEN signup_source IS NOT NULL AND signup_source != '' THEN 1 ELSE 0 END) +
                                (CASE WHEN tags IS NOT NULL AND tags != '' THEN 1 ELSE 0 END) +
                                (CASE WHEN meta IS NOT NULL AND JSON_LENGTH(meta) > 0 THEN JSON_LENGTH(meta) ELSE 0 END)
                            ) DESC,
                            -- Priority 3: Tie-breaker - keep oldest
                            id ASC
                    ) as rn
                    FROM emails 
                    WHERE email_list_id = $listId
                ) t WHERE rn = 1");

            $affected = DB::table('emails')
                ->where('email_list_id', $listId)
                ->whereNotIn('id', function ($query) {
                    $query->select('id')->from('temp_best_ids');
                })
                ->update([
                    'status' => 'duplicate',
                    'subscription_status' => 'unsubscribed'
                ]);

            DB::statement("DROP TEMPORARY TABLE temp_best_ids");

            $emailList->recalculateStats();
        });

        return response()->json([
            'success' => true,
            'message' => "Bulletproof scrubbing complete. All duplicates (including those with spaces/case differences) have been resolved."
        ]);
    }

    public function bulkAction(Request $request, EmailList $emailList)
    {
        $request->validate([
            'action' => 'required|in:unsubscribe,archive,unarchive,permanent_delete'
        ]);

        if ($request->global && $request->filters) {
            $query = $emailList->emails();
            $filters = $request->filters;

            if (isset($filters['status']) && $filters['status'] !== 'all')
                $query->where('status', $filters['status']);
            if (isset($filters['subscription']) && $filters['subscription'] !== 'all')
                $query->where('subscription_status', $filters['subscription']);
            if (isset($filters['segment']) && $filters['segment'] !== 'all')
                $query->where('segment_name', $filters['segment']);
            if (isset($filters['tag']) && $filters['tag'] !== 'all')
                $query->where('tags', 'like', '%' . $filters['tag'] . '%');
            if (isset($filters['source']) && $filters['source'] !== 'all')
                $query->where('signup_source', $filters['source']);
            if (isset($filters['archived'])) {
                if ($filters['archived'] === 'yes')
                    $query->where('is_archived', true);
                elseif ($filters['archived'] === 'no')
                    $query->where('is_archived', false);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $field = $filters['search_field'] ?? 'all';
                $query->where(function ($q) use ($search, $field) {
                    if ($field === 'all' || $field === 'email')
                        $q->orWhere('email', 'like', "%$search%");
                    if ($field === 'all' || $field === 'name')
                        $q->orWhere('name', 'like', "%$search%");
                });
            }
            $emails = $query;
        } else {
            $emails = $emailList->emails()->whereIn('id', $request->ids);
        }

        $count = $emails->count();

        if ($request->action === 'unsubscribe') {
            $emails->update(['subscription_status' => 'unsubscribed', 'unsubscribed_at' => now()]);
        } elseif ($request->action === 'archive') {
            $emails->update(['is_archived' => true, 'archived_at' => now()]);
        } elseif ($request->action === 'unarchive') {
            $emails->update(['is_archived' => false, 'archived_at' => null]);
        } elseif ($request->action === 'permanent_delete') {
            $emails->delete();
        }

        $emailList->recalculateStats();

        $emailList->activityLogs()->create([
            'user_id' => auth()->id(),
            'type' => 'bulk_action',
            'details' => [
                'action' => $request->action,
                'count' => $count,
                'scope' => $request->global ? 'global' : 'selection',
                'filters' => $request->global ? $request->filters : null
            ]
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Import more contacts into an existing list (ASYNC).
     */
    public function importMore(Request $request, EmailList $emailList, FileParserService $parser)
    {
        $request->validate(['import_type' => 'required|in:upload,paste']);

        if ($request->import_type === 'upload') {
            $request->validate(['file' => 'required|file|max:' . config('emailplatform.upload.max_file_size', 10240) . '|mimes:csv,xlsx,txt']);
            $file = $request->file('file');
            $path = $file->store('email-lists', 'local');

            $emailList->update([
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'tags' => $request->tags ?? $emailList->tags,
                'signup_source' => $request->signup_source ?? $emailList->signup_source,
                'status' => 'pending',
            ]);
            return $this->showMappingView($emailList, $parser, $file);
        }

        if ($request->import_type === 'paste') {
            $request->validate(['emails_text' => 'required|string']);

            $filename = 'paste_' . Str::random(10) . '.csv';
            $path = 'email-lists/' . $filename;
            Storage::disk('local')->put($path, "email\n" . $request->emails_text);

            $log = \App\Models\ActivityLog::create([
                'email_list_id' => $emailList->id,
                'user_id' => auth()->id(),
                'type' => 'import',
                'details' => [
                    'status' => 'started',
                    'source' => 'Paste',
                    'tags' => $request->tags ?? $emailList->tags,
                    'processed' => 0,
                    'valid' => 0,
                    'invalid' => 0,
                    'duplicate' => 0
                ]
            ]);

            $emailList->update([
                'status' => 'processing',
                'file_path' => $path,
                'tags' => $request->tags ?? $emailList->tags,
                'signup_source' => $request->signup_source ?? $emailList->signup_source,
                'column_mapping' => ['email' => 'email', '_settings' => ['skip_dns' => false]],
            ]);

            ProcessEmailListJob::dispatch($emailList->id, $log->id);

            return redirect()->route('admin.email-lists.show', $emailList)->with('success', 'Bulk import started in background.');
        }
    }

    public function undoImport(EmailList $emailList, int $logId)
    {
        $log = $emailList->activityLogs()->where('type', 'import')->findOrFail($logId);

        if (isset($log->details['undone'])) {
            return back()->with('error', 'This import has already been undone.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($emailList, $log) {
            // Delete only newly INSERTED records from this import session.
            // Restored/promoted records won't have this activity_log_id anymore
            // since we don't tag existing records with new log IDs.
            $emailList->emails()
                ->where('activity_log_id', $log->id)
                ->delete();

            // Recalculate stats
            $emailList->recalculateStats();

            // Mark log as undone
            $details = $log->details;
            $details['undone'] = true;
            $details['undone_at'] = now()->toDateTimeString();
            $log->update(['details' => $details]);
        });

        return back()->with('success', 'Import has been successfully undone. Records removed.');
    }

    public function fixInvalid(EmailList $emailList)
    {
        // Get ALL invalid emails for this list
        $emails = $emailList->emails()
            ->where('status', 'invalid')
            ->latest()
            ->get()
            ->map(function ($e) {
                // Real-time MX check for the UI
                $domain = substr(strrchr($e->email, "@"), 1);
                $e->domain_invalid = $domain ? !@checkdnsrr($domain, "MX") : true;
                return $e;
            });

        return view('email-lists.fix-invalid', compact('emailList', 'emails'));
    }

    /**
     * Bulk save and re-validate corrected invalid emails
     */
    public function saveInvalid(Request $request, EmailList $emailList, EmailValidationService $validator)
    {
        $input = $request->input('emails', []);

        if (empty($input)) {
            return response()->json(['success' => false, 'message' => 'No data to save.']);
        }

        $results = $validator->validateBatch($input, $emailList->id);

        DB::transaction(function () use ($results) {
            // 1. Handle Validated (Fixed) Records
            foreach ($results['valid'] as $entry) {
                Email::where('id', $entry['id'])->update([
                    'email' => $entry['email'],
                    'name' => $entry['name'],
                    'status' => 'valid',
                    'subscription_status' => 'subscribed',
                    'reason' => null
                ]);
            }

            // 2. Handle Duplicates
            foreach ($results['duplicate'] as $entry) {
                Email::where('id', $entry['id'])->update([
                    'email' => $entry['email'],
                    'name' => $entry['name'],
                    'status' => 'duplicate',
                    'reason' => 'Email already exists and is active/valid in this list'
                ]);
            }

            // 3. Handle Still Invalid
            foreach ($results['invalid'] as $entry) {
                Email::where('id', $entry['id'])->update([
                    'email' => $entry['email'],
                    'name' => $entry['name'],
                    'status' => 'invalid',
                    'reason' => $entry['reason']
                ]);
            }
        });

        // Recalculate stats for the list
        $emailList->recalculateStats();

        return response()->json([
            'success' => true,
            'message' => 'Records processed successfully.',
            'summary' => [
                'fixed' => count($results['valid']),
                'duplicate' => count($results['duplicate']),
                'invalid' => count($results['invalid']),
            ]
        ]);
    }
}
