<?php

namespace App\Http\Controllers;

use App\Exports\ContactsExport;
use App\Models\EmailList;
use App\Models\Email;
use App\Services\FileParserService;
use App\Jobs\ProcessEmailListJob;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
        $query = EmailList::query();
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            $query->where(function($q) use ($teamUserId) {
                $q->where('is_public', true)
                  ->orWhere('created_by_id', $teamUserId);
            });
        }
        $lists = $query->latest()->paginate(15);

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
        // Check Limits
        if (auth()->user()->getContactsUsage()->is_exceeded) {
            return redirect()->route('admin.email-lists.index')->with('error', 'Contact limit exceeded. Please upgrade your plan before creating new lists.');
        }

        $request->validate([
            'import_type' => 'required|in:upload,manual,paste',
            'name' => 'required|string|max:255',
            'is_public' => 'nullable',
        ]);

        $listName = $request->name;
        $isPublic = $request->has('is_public') ? (bool) $request->is_public : true;

        $teamPermissions = [
            'add_contact' => $request->has('team_permissions.add_contact'),
            'edit_contact' => $request->has('team_permissions.edit_contact'),
            'delete_contact' => $request->has('team_permissions.delete_contact'),
        ];

        $emailList = EmailList::create([
            'name' => $listName,
            'list_type' => EmailList::TYPE_DUAL,
            'signup_source' => $request->signup_source ?? 'Direct Import',
            'status' => 'pending',
            'is_public' => $isPublic,
            'created_by_id' => app()->has('team_user') ? app('team_user')->id : auth()->id(),
            'team_permissions' => $teamPermissions,
        ]);

        // 1. Handling File Upload
        if ($request->import_type === 'upload') {
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'max:' . config('emailplatform.upload.max_file_size', 10240),
                    function ($attribute, $value, $fail) {
                        $extension = strtolower($value->getClientOriginalExtension());
                        if (!in_array($extension, ['csv', 'xlsx', 'txt'])) {
                            $fail('The file must be a file of type: csv, xlsx, txt.');
                        }
                    }
                ],
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

            // Since we don't know the format, we'll try to treat it as a headerless CSV
            // and use auto-mapping in the next step.
            Storage::disk('local')->put($path, $request->emails_text);

            $emailList->update([
                'file_path' => $path,
                'original_filename' => 'bulk_paste.csv',
                'signup_source' => $request->signup_source ?? 'Bulk Paste',
                'status' => 'pending'
            ]);

            return $this->showMappingView($emailList, $parser, new UploadedFile(Storage::disk('local')->path($path), 'bulk_paste.csv'));

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
                'list_type' => EmailList::TYPE_DUAL,
            ]);

            $tags = null;
            if ($request->filled('manual_tags')) {
                $tagsArray = array_map('trim', array_filter(explode(',', $request->manual_tags)));
                $tags = !empty($tagsArray) ? $tagsArray : null;
            }

            $validator = app(\App\Services\EmailValidationService::class);
            $validationResults = $validator->validateBatch([
                [
                    'email' => $request->manual_email,
                    'name' => $request->manual_name,
                    'whatsapp_number' => $request->manual_whatsapp,
                    'meta' => [],
                ]
            ], $emailList->id, false);

            $processedEntry = null;
            $finalStatus = 'valid';
            $finalSubStatus = 'subscribed';
            
            foreach (['valid', 'invalid', 'duplicate', 'to_restore', 'to_valid', 'cross_duplicate'] as $cat) {
                if (!empty($validationResults[$cat])) {
                    $processedEntry = $validationResults[$cat][0];
                    if ($cat === 'invalid') {
                        $finalStatus = 'invalid';
                        $finalSubStatus = 'unsubscribed';
                    } elseif ($cat === 'cross_duplicate') {
                        $finalStatus = 'cross_duplicate';
                    } elseif ($cat === 'duplicate') {
                        return back()->withErrors(['manual_email' => 'This contact already exists in the current list.']);
                    }
                    break;
                }
            }

            if (!$processedEntry) {
                return back()->withErrors(['manual_email' => 'Failed to process email.']);
            }

            Email::create([
                'user_id' => auth()->id(),
                'email_list_id' => $emailList->id,
                'email' => $processedEntry['email'] ?? $request->manual_email,
                'name' => $request->manual_name,
                'whatsapp_number' => $processedEntry['whatsapp_number'] ?? $request->manual_whatsapp,
                'whatsapp_opt_in' => $processedEntry['whatsapp_opt_in'] ?? true,
                'whatsapp_subscription_status' => $processedEntry['whatsapp_subscription_status'] ?? 'subscribed',
                'tags' => $tags,
                'status' => $finalStatus,
                'subscription_status' => $finalSubStatus,
                'email_status' => $processedEntry['email_status'] ?? 'valid',
                'email_score' => $processedEntry['email_score'] ?? 5,
                'email_risk_level' => $processedEntry['email_risk_level'] ?? 'low',
                'is_role_based' => $processedEntry['is_role_based'] ?? false,
                'is_disposable' => $processedEntry['is_disposable'] ?? false,
                'is_catch_all' => $processedEntry['is_catch_all'] ?? false,
                'has_typo' => $processedEntry['has_typo'] ?? false,
                'validation_reason' => $processedEntry['validation_reason'] ?? null,
                'meta' => isset($processedEntry['meta']) && !empty($processedEntry['meta']) ? $processedEntry['meta'] : null,
            ]);
            
            $emailList->recalculateStats();

            return redirect()->route('admin.email-lists.show', $emailList)->with('success', 'Contact added successfully.');
        }
    }

    public function updateName(Request $request, EmailList $emailList)
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if ($emailList->created_by_id !== $teamUserId) {
                abort(403, 'Only the creator can rename this list.');
            }
        }

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
        $customColumns = []; // Columns to store as-is in meta

        foreach ($rawMapping as $excelColumn => $systemField) {
            if ($systemField === '__custom__') {
                // Store column name so FileParserService saves it in meta
                $customColumns[] = $excelColumn;
            } elseif (!empty($systemField)) {
                // Explicitly mapped to a known system field
                $finalMapping[$systemField] = $excelColumn;
            }
            // empty $systemField = "Skip Column" → discard
        }

        if (!isset($finalMapping['email'])) {
            return back()->withErrors(['mapping' => 'You must map the Email Address column.']);
        }

        // Store list of custom columns to preserve in meta
        if (!empty($customColumns)) {
            $finalMapping['_custom_columns'] = $customColumns;
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
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list is private.');
            }
        }

        $stats = $emailList->getStatistics();

        // Match Alpine.js default filter: Active Only (is_archived = false)
        $emails = $emailList->emails()->with(['deals.stage.pipeline'])->where('is_archived', false)->orderBy('created_at', 'desc')->paginate(50);

        // Cache filters (segments, tags, sources) in Redis with 15s TTL
        $filterCacheKey = "list_filters:{$emailList->id}";
        $cachedFilters = \Illuminate\Support\Facades\Redis::get($filterCacheKey);
        
        if ($cachedFilters) {
            $decodedFilters = json_decode($cachedFilters, true);
            $segments = $decodedFilters['segments'] ?? [];
            $tags = $decodedFilters['tags'] ?? [];
            $sources = $decodedFilters['sources'] ?? [];
        } else {
            $customSegments = $emailList->emails()->whereNotNull('segment_name')->distinct()->pluck('segment_name')->toArray();
            $autoSegments = \App\Services\SegmentService::getAutoSegmentsList();
            $segments = array_merge($customSegments, $autoSegments);
            
            // Normalize and unique tags
            $rawTags = $emailList->emails()->whereNotNull('tags')->distinct()->pluck('tags')->toArray();
            $flatTags = [];
            foreach ($rawTags as $tagVal) {
                if (is_array($tagVal)) {
                    $flatTags = array_merge($flatTags, $tagVal);
                } elseif (is_string($tagVal)) {
                    $decoded = json_decode($tagVal, true);
                    if (is_array($decoded)) {
                        $flatTags = array_merge($flatTags, $decoded);
                    } else {
                        $flatTags[] = $tagVal;
                    }
                }
            }
            $tags = array_values(array_unique(array_filter($flatTags)));

            $sources = $emailList->emails()->whereNotNull('signup_source')->distinct()->pluck('signup_source')->toArray();

            $decodedFilters = [
                'segments' => $segments,
                'tags' => $tags,
                'sources' => $sources
            ];

            \Illuminate\Support\Facades\Redis::setex($filterCacheKey, 86400, json_encode($decodedFilters));
        }

        return view('email-lists.show', compact('emailList', 'stats', 'emails', 'segments', 'tags', 'sources'));
    }

    public function filterEmails(Request $request, EmailList $emailList)
    {
        $groupExpr = "CASE WHEN name IS NOT NULL AND TRIM(name) != '' THEN CONCAT('name_', LOWER(TRIM(name))) WHEN original_row_id IS NOT NULL AND TRIM(original_row_id) != '' THEN CONCAT('orig_', original_row_id) ELSE CONCAT('id_', id) END";

        $query = $emailList->emails()->with(['deals.stage.pipeline'])->orderBy('created_at', 'desc');

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
            $value = $request->segment;
            $query->where(function($q) use ($value) {
                $q->where('segment_name', $value)
                  ->orWhereJsonContains('auto_segments', $value);
            });
        }

        // Source filter
        if ($request->source && $request->source !== 'all') {
            $query->where('signup_source', $request->source);
        }

        // Tag filter
        if ($request->tag && $request->tag !== 'all') {
            $query->where('tags', 'like', "%{$request->tag}%");
        }

        // Channel filter (Presence)
        if ($request->channel === 'only_email') {
            $query->whereNotNull('email')->where('email', '!=', '');
        } elseif ($request->channel === 'only_whatsapp') {
            $query->whereNotNull('whatsapp_number')->where('whatsapp_number', '!=', '');
        }

        // WhatsApp Subscription Status filter
        if ($request->wa_status && $request->wa_status !== 'all') {
            $query->where('whatsapp_subscription_status', $request->wa_status);
        }

        // Targeted Search
        if ($request->search) {
            $search = $request->search;
            $field = $request->search_field ?? 'name';

            $query->where(function ($q) use ($search, $field) {
                if ($field === 'email') {
                    $q->whereRaw("LOWER(email) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'name') {
                    $q->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'whatsapp_number') {
                    $q->whereRaw("LOWER(whatsapp_number) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'segment') {
                    $q->whereRaw("LOWER(segment_name) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'tag') {
                    $q->whereRaw("LOWER(tags) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'source') {
                    $q->whereRaw("LOWER(signup_source) LIKE ?", ["%" . strtolower($search) . "%"]);
                } else {
                    // Search in meta JSON for specific keys (city, company, country, etc)
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}'))) LIKE ?", ["%" . strtolower($search) . "%"]);
                }
            });
        }

        $stats = $emailList->getStatistics();

        // 1. Calculate stats for the FULL LIST (ignoring current filters)
        $globalStats = [
            'valid' => $emailList->valid_count,
            'invalid' => $emailList->invalid_count,
            'duplicate' => $emailList->duplicate_count,
            'cross_duplicate' => $emailList->cross_duplicate_count,
            'subscribed' => $stats['subscribed'],
            'segment' => ($request->segment && $request->segment !== 'all') ? $emailList->emails()->where('segment_name', $request->segment)->count() : 0,
            'tag' => ($request->tag && $request->tag !== 'all') ? $emailList->emails()->where('tags', 'like', "%{$request->tag}%")->count() : 0,
            'source' => ($request->source && $request->source !== 'all') ? $emailList->emails()->where('signup_source', $request->source)->count() : 0,
        ];

        // 2. Calculate stats for the CURRENT filtered set
        $statsQuery = clone $query;
        
        $dbStats = (clone $statsQuery)
            ->reorder() // Remove orderBy from count query for performance
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) as valid,
                SUM(CASE WHEN status = 'invalid' THEN 1 ELSE 0 END) as invalid,
                SUM(CASE WHEN status = 'duplicate' THEN 1 ELSE 0 END) as duplicate,
                SUM(CASE WHEN status = 'cross_duplicate' THEN 1 ELSE 0 END) as cross_duplicate,
                SUM(CASE WHEN subscription_status = 'subscribed' THEN 1 ELSE 0 END) as subscribed,
                SUM(CASE WHEN subscription_status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
                SUM(CASE WHEN whatsapp_subscription_status = 'unsubscribed' THEN 1 ELSE 0 END) as whatsapp_unsubscribed,
                SUM(CASE WHEN subscription_status = 'bounced' THEN 1 ELSE 0 END) as bounced
            ")->first();

        $dynamicStats = [
            'total' => (int) ($dbStats->total ?? 0),
            'valid' => (int) ($dbStats->valid ?? 0),
            'invalid' => (int) ($dbStats->invalid ?? 0),
            'duplicate' => (int) ($dbStats->duplicate ?? 0),
            'cross_duplicate' => (int) ($dbStats->cross_duplicate ?? 0),
            'subscribed' => (int) ($dbStats->subscribed ?? 0),
            'unsubscribed' => (int) ($dbStats->unsubscribed ?? 0),
            'whatsapp_unsubscribed' => (int) ($dbStats->whatsapp_unsubscribed ?? 0),
            'bounced' => (int) ($dbStats->bounced ?? 0),
            
            // Advanced counts loaded from cached list statistics
            'global_main_rows' => $stats['global_main_rows'],
            'total_emails' => $stats['total_emails'],
            'subscribed_emails' => $stats['subscribed_emails'],
            'total_whatsapps' => $stats['total_whatsapps'],
            'subscribed_whatsapps' => $stats['subscribed_whatsapps'],
        ];

        // Check if filter is applied
        $isFiltered = false;
        if (($request->status && $request->status !== 'all') ||
            ($request->subscription && $request->subscription !== 'all') ||
            ($request->segment && $request->segment !== 'all') ||
            ($request->source && $request->source !== 'all') ||
            ($request->tag && $request->tag !== 'all') ||
            ($request->channel && $request->channel !== 'all') ||
            ($request->wa_status && $request->wa_status !== 'all') ||
            $request->search) {
            $isFiltered = true;
        }

        $dynamicStats['is_filtered'] = $isFiltered;
        $dynamicStats['filtered_main_rows'] = $isFiltered ? DB::table('emails')
            ->whereIn('id', (clone $query)->reorder()->select('emails.id'))
            ->distinct()
            ->count(DB::raw($groupExpr)) : 0;

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
            $value = $request->segment;
            $query->where(function($q) use ($value) {
                $q->where('segment_name', $value)
                  ->orWhereJsonContains('auto_segments', $value);
            });
        }
        if ($request->source && $request->source !== 'all') {
            $query->where('signup_source', $request->source);
        }
        if ($request->tag && $request->tag !== 'all') {
            $query->where('tags', 'like', "%{$request->tag}%");
        }
        if ($request->search) {
            $search = $request->search;
            $field = $request->search_field ?? 'name';

            $query->where(function ($q) use ($search, $field) {
                if ($field === 'email') {
                    $q->whereRaw("LOWER(email) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'name') {
                    $q->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'whatsapp_number') {
                    $q->whereRaw("LOWER(whatsapp_number) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'segment') {
                    $q->whereRaw("LOWER(segment_name) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'tag') {
                    $q->whereRaw("LOWER(tags) LIKE ?", ["%" . strtolower($search) . "%"]);
                } elseif ($field === 'source') {
                    $q->whereRaw("LOWER(signup_source) LIKE ?", ["%" . strtolower($search) . "%"]);
                } else {
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}'))) LIKE ?", ["%" . strtolower($search) . "%"]);
                }
            });
        }

        // Determine extra CRM fields from the list's column mapping
        // This ensures we export exactly what the user mapped/sees in the grid
        $mapping = $emailList->column_mapping ?? [];
        $internalFields = ['email', 'name', 'whatsapp_number', 'phone', 'segment_name', 'signup_source', '_settings'];
        $extraFields = [];

        foreach ($mapping as $field => $index) {
            if (!in_array($field, $internalFields)) {
                $extraFields[] = $field;
            }
        }

        // Add common fields if they exist in mapping but were missed
        foreach (['company', 'city', 'country'] as $f) {
            if (isset($mapping[$f]) && !in_array($f, $extraFields)) {
                $extraFields[] = $f;
            }
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

        $isConsolidated = $request->input('consolidate') === '1';

        if ($isConsolidated) {
            // Consolidation logic: Group by original_row_id (or ID if null)
            // Use ANY_VALUE for non-aggregated columns to satisfy only_full_group_by
            $query->selectRaw('ANY_VALUE(original_row_id) as original_row_id')
                  ->selectRaw('ANY_VALUE(name) as name')
                  ->selectRaw('ANY_VALUE(meta) as meta')
                  ->selectRaw('ANY_VALUE(created_at) as created_at')
                  ->selectRaw('ANY_VALUE(tags) as tags')
                  ->selectRaw('ANY_VALUE(email_status) as email_status')
                  ->selectRaw('ANY_VALUE(status) as status')
                  ->selectRaw('ANY_VALUE(validation_reason) as validation_reason')
                  ->selectRaw('ANY_VALUE(reason) as reason')
                  ->selectRaw('GROUP_CONCAT(DISTINCT email SEPARATOR ", ") as email')
                  ->selectRaw('GROUP_CONCAT(DISTINCT whatsapp_number SEPARATOR ", ") as whatsapp_number')
                  ->groupBy(\Illuminate\Support\Facades\DB::raw("CASE WHEN name IS NOT NULL AND TRIM(name) != '' THEN CONCAT('name_', LOWER(TRIM(name))) ELSE COALESCE(original_row_id, CAST(id AS CHAR)) END"))
                  ->reorder()
                  ->orderByRaw('ANY_VALUE(created_at) DESC');
        }

        return Excel::download(new ContactsExport($query, $extraFields), $filename);
    }

    public function destroyEmail(Request $request, EmailList $emailList, int $emailId)
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list is private.');
            }
        }
        if (!$emailList->canPerformAction('delete_contact')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to delete contacts from this list.'], 403);
        }

        $email = $emailList->emails()->findOrFail($emailId);
        $status = $email->status;
        $is_archived = $email->is_archived;
        
        $reason = $request->input('reason', 'User requested permanent deletion');
        
        $suppressions = [];
        $now = now()->toDateTimeString();
        
        if (!empty($email->email)) {
            $suppressions[] = [
                'email_list_id' => $emailList->id,
                'identifier' => $email->email,
                'reason' => $reason,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        if (!empty($email->whatsapp_number)) {
            $suppressions[] = [
                'email_list_id' => $emailList->id,
                'identifier' => $email->whatsapp_number,
                'reason' => $reason,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        
        if (count($suppressions) > 0) {
            \App\Models\EmailListSuppression::upsert(
                $suppressions, 
                ['email_list_id', 'identifier'], 
                ['reason', 'updated_at']
            );
        }

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
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if ($emailList->created_by_id !== $teamUserId) {
                abort(403, 'Only the creator can delete this list.');
            }
        }

        if ($emailList->file_path)
            Storage::disk('local')->delete($emailList->file_path);
        $emailList->delete();
        return redirect()->route('admin.email-lists.index')->with('success', 'Email list deleted successfully.');
    }

    public function checkStatus(EmailList $emailList)
    {
        $stats = $emailList->getStatistics();

        $data = [
            'status' => $emailList->status,
            'total_records' => $emailList->total_records,
            'valid_count' => $emailList->valid_count,
            'invalid_count' => $emailList->invalid_count,
            'duplicate_count' => $emailList->duplicate_count,
            'cross_duplicate_count' => $emailList->cross_duplicate_count,
            'subscribed_count' => $stats['subscribed'],
            'unsubscribed_count' => $stats['unsubscribed'],
            'bounced_count' => $stats['bounced'],
            'hard_bounce_count' => $stats['hard_bounce'],
            'soft_bounce_count' => $stats['soft_bounce'],
            'complaint_count' => $stats['complaints'],
            'risky_count' => $stats['risky'],
            'disposable_count' => $stats['disposable'],
            'role_based_count' => $stats['role_based'],
            'suspicious_count' => $stats['suspicious'],
            'archived_count' => $stats['archived'],
            
            // Advanced counts
            'global_main_rows' => $stats['global_main_rows'],
            'total_emails' => $stats['total_emails'],
            'subscribed_emails' => $stats['subscribed_emails'],
            'total_whatsapps' => $stats['total_whatsapps'],
            'subscribed_whatsapps' => $stats['subscribed_whatsapps'],
        ];

        // Check for active batch progress
        $latestLog = $emailList->activityLogs()->where('type', 'import')->where('details->status', 'started')->latest()->first();
        if ($latestLog && $latestLog->batch_id) {
            $batch = Bus::findBatch($latestLog->batch_id);
            if ($batch) {
                $data['import_progress'] = $batch->progress();
                
                // Merge real-time atomic session counters into the details for the UI
                $details = $latestLog->details ?? [];
                $details['processed'] = (int) $latestLog->session_valid_count + (int) $latestLog->session_invalid_count + (int) $latestLog->session_duplicate_count + (int) $latestLog->session_cross_duplicate_count;
                $details['valid']     = (int) $latestLog->session_valid_count;
                $details['invalid']   = (int) $latestLog->session_invalid_count;
                $details['duplicate'] = (int) $latestLog->session_duplicate_count;
                $details['cross_duplicate'] = (int) $latestLog->session_cross_duplicate_count;
                
                $data['import_details'] = $details;

                // Safety: If progress is 100%, persist completion to DB to avoid refresh loops
                if ($data['import_progress'] >= 100 && $emailList->status === 'processing') {
                    $emailList->update(['status' => 'completed']);
                    $emailList->recalculateStats();
                    $data['status'] = 'completed';

                    // Also finalize the log if it was left hanging
                    if ($latestLog && ($latestLog->details['status'] ?? '') === 'started') {
                        $latestLog->update([
                            'details' => array_merge($latestLog->details, [
                                'status' => 'completed',
                                'finished_at' => now()->toDateTimeString(),
                                'processed' => $details['processed'],
                                'valid' => $details['valid'],
                                'invalid' => $details['invalid'],
                                'duplicate' => $details['duplicate'],
                                'cross_duplicate' => $details['cross_duplicate'],
                            ])
                        ]);
                    }
                }
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
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list is private.');
            }
        }
        if (!$emailList->canPerformAction('edit_contact')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to edit contacts in this list.'], 403);
        }

        $email = $emailList->emails()->findOrFail($emailId);
        $request->validate([
            'email' => 'nullable|email', 
            'name' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string'
        ]);

        $data = $request->only([
            'email', 'name', 'whatsapp_number', 'segment_name', 
            'signup_source', 'subscription_status', 'whatsapp_subscription_status', 'tags'
        ]);

        if (!empty($data['whatsapp_number']) && $email->original_row_id) {
            $phoneExistsInGroup = $emailList->emails()
                ->where('original_row_id', $email->original_row_id)
                ->where('id', '!=', $email->id)
                ->where('whatsapp_number', $data['whatsapp_number'])
                ->exists();
            if ($phoneExistsInGroup) {
                $data['whatsapp_number'] = null; // Clear it to avoid duplicating within the group
            }
        }

        // Sync WhatsApp Opt-in based on status
        if (isset($data['whatsapp_subscription_status'])) {
            $data['whatsapp_opt_in'] = ($data['whatsapp_subscription_status'] === 'subscribed');
            if (!$data['whatsapp_opt_in'] && $email->whatsapp_subscription_status === 'subscribed') {
                $data['whatsapp_unsubscribed_at'] = now();
            } elseif ($data['whatsapp_opt_in']) {
                $data['whatsapp_unsubscribed_at'] = null;
            }
        }

        // Handle subscription status changes and temporary unsubscribe duration
        if (isset($data['subscription_status'])) {
            if ($data['subscription_status'] === 'unsubscribed') {
                if ($email->subscription_status !== 'unsubscribed') {
                    $data['unsubscribed_at'] = now();
                }
                
                $duration = $request->input('unsubscribe_duration', 'forever');
                if ($duration !== 'forever') {
                    $days = (int) $duration;
                    if ($days > 0) {
                        $data['unsubscribe_expires_at'] = now()->addDays($days);
                    }
                } else {
                    $data['unsubscribe_expires_at'] = null;
                }
            } else {
                $data['unsubscribed_at'] = null;
                $data['unsubscribe_expires_at'] = null;
            }
        }

        if ($request->has('meta')) {
            $data['meta'] = array_merge($email->meta ?? [], $request->meta);
        }

        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_map('trim', array_filter(explode(',', $data['tags'])));
        }

        $oldName = $email->name;

        $email->update($data);

        // Advanced CRM Group Sync: Propagate shared details (name, segment_name, tags, meta, signup_source)
        // to other channels belonging to the same contact/person.
        $syncData = [];
        if ($request->has('name')) $syncData['name'] = $data['name'];
        if ($request->has('segment_name')) $syncData['segment_name'] = $data['segment_name'];
        if ($request->has('tags')) $syncData['tags'] = $data['tags'];
        if ($request->has('meta')) $syncData['meta'] = $data['meta'];
        if ($request->has('signup_source')) $syncData['signup_source'] = $data['signup_source'];

        if (!empty($syncData)) {
            $qbSyncData = $syncData;
            if (isset($qbSyncData['tags'])) {
                $qbSyncData['tags'] = json_encode($qbSyncData['tags']);
            }
            if (isset($qbSyncData['meta'])) {
                $qbSyncData['meta'] = json_encode($qbSyncData['meta']);
            }

            if (!empty(trim($oldName))) {
                $groupId = $email->original_row_id ?: (string) \Illuminate\Support\Str::uuid();
                if (!$email->original_row_id) {
                    $email->update(['original_row_id' => $groupId]);
                }
                
                $qbSyncData['original_row_id'] = $groupId;
                
                $emailList->emails()
                    ->where(function($q) use ($groupId, $oldName) {
                        $q->where('original_row_id', $groupId)
                          ->orWhere('name', $oldName);
                    })
                    ->where('id', '!=', $email->id)
                    ->update($qbSyncData);
            } elseif ($email->original_row_id) {
                $emailList->emails()
                    ->where('original_row_id', $email->original_row_id)
                    ->where('id', '!=', $email->id)
                    ->update($qbSyncData);
            }
        }

        // Recalculate segments for this contact
        \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $email->id);

        $emailList->recalculateStats();

        return response()->json(['success' => true]);
    }

    public function addContact(Request $request, EmailList $emailList)
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list is private.');
            }
        }
        if (!$emailList->canPerformAction('add_contact')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to add contacts to this list.'], 403);
        }

        $request->validate([
            'email' => 'required|email', 
            'name' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:20'
        ]);

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
                    'whatsapp_number' => $request->whatsapp_number ?? $existing->whatsapp_number,
                    'status' => 'valid',
                    'subscription_status' => 'subscribed',
                    'signup_source' => $request->signup_source ?? $existing->signup_source,
                ]);
                
                // Recalculate segments for this contact
                \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $existing->id);

                return response()->json(['success' => true, 'message' => 'Archived contact restored and updated.']);
            }

            // If exists and not archived, create a duplicate entry as usual for tracking
            $email = $emailList->emails()->create([
                'user_id' => auth()->id(),
                'email' => $request->email,
                'name' => $request->name,
                'whatsapp_number' => $request->whatsapp_number,
                'segment_name' => $request->segment_name,
                'tags' => $request->tags,
                'signup_source' => $request->signup_source ?? 'Manual',
                'status' => 'duplicate',
                'subscription_status' => 'subscribed',
                'is_archived' => false
            ]);
        } else {
            $email = $emailList->emails()->create([
                'user_id' => auth()->id(),
                'email' => $request->email,
                'name' => $request->name,
                'whatsapp_number' => $request->whatsapp_number,
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

        // Recalculate segments for this contact
        \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $email->id);

        $emailList->recalculateStats();

        return response()->json(['success' => true]);
    }

    public function addAlternateChannel(Request $request, EmailList $emailList)
    {
        $request->validate([
            'original_row_id' => 'required|string',
            'email' => 'nullable|email',
            'whatsapp_number' => 'nullable|string|max:20'
        ]);

        if (!$request->email && !$request->whatsapp_number) {
            return response()->json(['success' => false, 'message' => 'Provide either an email or WhatsApp number.'], 422);
        }

        // Get the parent/main contact to inherit basic properties
        $parentContact = $emailList->emails()
            ->where(function($q) use ($request) {
                $q->where('original_row_id', $request->original_row_id)
                  ->orWhere('id', $request->original_row_id);
            })
            ->first();
            
        if (!$parentContact) {
            return response()->json(['success' => false, 'message' => 'Parent contact not found.'], 404);
        }

        // If parent contact doesn't have an original_row_id, generate one and save it
        $groupId = $parentContact->original_row_id;
        if (!$groupId) {
            $groupId = (string) \Illuminate\Support\Str::uuid();
            $parentContact->update(['original_row_id' => $groupId]);
        }

        if ($request->email) {
            $banned = $emailList->emails()->where('email', $request->email)->where('status', 'permanent_delete')->exists();
            if ($banned) {
                return response()->json(['success' => false, 'message' => 'This email has been permanently banished from this list.'], 422);
            }
        }

        $whatsappNumber = $request->whatsapp_number;
        if ($whatsappNumber) {
            $phoneExistsInGroup = $emailList->emails()
                ->where('original_row_id', $groupId)
                ->where('whatsapp_number', $whatsappNumber)
                ->exists();
            if ($phoneExistsInGroup) {
                $whatsappNumber = null;
            }
        }

        if (!$request->email && empty($whatsappNumber)) {
            return response()->json(['success' => false, 'message' => 'This WhatsApp number already exists in this contact group.'], 422);
        }

        $newContact = $emailList->emails()->create([
            'user_id' => auth()->id(),
            'email' => $request->email ?? '',
            'whatsapp_number' => $whatsappNumber,
            'name' => $parentContact->name,
            'segment_name' => $parentContact->segment_name,
            'tags' => $parentContact->tags,
            'meta' => $parentContact->meta,
            'signup_source' => 'Manual Entry',
            'status' => 'valid',
            'subscription_status' => 'subscribed',
            'is_archived' => false,
            'original_row_id' => $groupId,
        ]);

        $emailList->increment('total_records');
        $emailList->increment('valid_count');

        \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $newContact->id);

        $emailList->recalculateStats();

        return response()->json(['success' => true]);
    }

    public function addCustomColumn(Request $request, EmailList $emailList)
    {
        $request->validate([
            'column_name' => 'required|string|max:100',
        ]);

        $mapping = $emailList->column_mapping ?? [];
        
        // Generate a machine-readable key (e.g., "custom_industry_type")
        $key = 'custom_' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $request->column_name));
        $key = trim($key, '_');
        
        // Add if it doesn't already exist
        if (!isset($mapping[$key])) {
            $mapping[$key] = $request->column_name;
            $emailList->column_mapping = $mapping;
            $emailList->save();
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
            'action' => 'required|in:unsubscribe,subscribe,archive,unarchive,permanent_delete,edit_column,update_column'
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
            $duration = $request->input('duration', 'forever');
            $expiresAt = null;
            if ($duration !== 'forever') {
                $days = (int) $duration;
                if ($days > 0) {
                    $expiresAt = now()->addDays($days);
                }
            }

            $emailIds = $emails->pluck('id')->toArray();

            $emails->update([
                'subscription_status' => 'unsubscribed',
                'unsubscribed_at' => now(),
                'unsubscribe_expires_at' => $expiresAt
            ]);

            foreach ($emailIds as $id) {
                \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $id);
            }
        } elseif ($request->action === 'subscribe') {
            $emailIds = $emails->pluck('id')->toArray();
            $emails->update([
                'subscription_status' => 'subscribed',
                'unsubscribed_at' => null,
                'unsubscribe_expires_at' => null
            ]);
            foreach ($emailIds as $id) {
                \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $id);
            }
        } elseif ($request->action === 'archive') {
            $emails->update(['is_archived' => true, 'archived_at' => now()]);
        } elseif ($request->action === 'unarchive') {
            $emails->update(['is_archived' => false, 'archived_at' => null]);
        } elseif ($request->action === 'permanent_delete' || $request->action === 'delete') {
            $reason = $request->input('delete_reason', $request->input('reason', 'User requested permanent deletion'));
            
            \App\Jobs\BulkPermanentDeleteJob::dispatch(
                $emailList->id,
                $request->global ? true : false,
                $request->global ? $request->filters : null,
                $request->global ? [] : $request->ids,
                $reason
            );

            $emailList->activityLogs()->create([
                'user_id' => auth()->id(),
                'type' => 'bulk_action',
                'details' => [
                    'action' => 'permanent_delete',
                    'count' => $count,
                    'scope' => $request->global ? 'global' : 'selection',
                    'filters' => $request->global ? $request->filters : null
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'The permanent delete process has started in the background. Depending on the size, it may take a few minutes.'
            ]);
        } elseif ($request->action === 'edit_column' || $request->action === 'update_column') {
            $column = $request->input('column') ?? $request->input('target_column');
            $value = $request->input('value') ?? $request->input('new_value');
            if ($column) {
                // If value is empty or null, we might want to clear it
                if ($value === null) $value = '';
                
                // standard columns
                if (in_array($column, ['name', 'company', 'job_title', 'phone', 'city', 'tags', 'country'])) {
                    $emails->update([$column => $value]);
                } else if (str_starts_with($column, 'custom_')) {
                    // Updating JSON column 'meta' in bulk
                    // We can use JSON_SET in MySQL for better performance and atomicity
                    $emails->update([
                        'meta' => DB::raw("JSON_SET(COALESCE(meta, '{}'), '$.$column', " . DB::getPdo()->quote($value) . ")")
                    ]);
                }
            }
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
        // Check Limits
        if (auth()->user()->getContactsUsage()->is_exceeded) {
            return redirect()->back()->with('error', 'Contact limit exceeded. Please upgrade your plan before importing more contacts.');
        }

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
        // Load all invalid emails directly — no DNS re-check (too slow for large lists)
        $emails = $emailList->emails()
            ->where('status', 'invalid')
            ->latest()
            ->get();

        return view('email-lists.fix-invalid', compact('emailList', 'emails'));
    }

    /**
     * Delete ALL invalid emails for a list in one query (bulk delete)
     */
    public function deleteAllInvalid(EmailList $emailList)
    {
        $deleted = $emailList->emails()
            ->where('status', 'invalid')
            ->delete();

        $emailList->recalculateStats();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => "{$deleted} invalid records deleted successfully."
        ]);
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

            // 4. Handle Cross-List Duplicates
            foreach ($results['cross_duplicate'] as $entry) {
                $emailRecord = Email::find($entry['id']);
                if ($emailRecord) {
                    $emailRecord->update([
                        'email' => $entry['email'],
                        'name' => $entry['name'],
                        'status' => 'cross_duplicate',
                        'meta' => array_merge($emailRecord->meta ?? [], $entry['meta'] ?? []),
                        'reason' => 'Exists in other lists'
                    ]);
                }
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
                'cross_duplicate' => count($results['cross_duplicate']),
            ]
        ]);
    }

    public function resolveDuplicates(EmailList $emailList)
    {
        $duplicates = $emailList->emails()->where('status', 'cross_duplicate')->paginate(20);
        return view('email-lists.duplicates', compact('emailList', 'duplicates'));
    }

    public function resolveDuplicatesAction(Request $request, EmailList $emailList)
    {
        $request->validate([
            'resolutions' => 'nullable|array',
            'resolutions.*' => 'in:keep_old,move_new,keep_both',
            'bulk_action' => 'nullable|in:keep_old,move_new,keep_both',
            'selected_ids' => 'nullable|array',
            'selected_ids.*' => 'exists:emails,id',
        ]);

        $resolutions = $request->input('resolutions', []);
        $bulkAction = $request->input('bulk_action');
        $selectedIds = $request->input('selected_ids', []);

        $query = $emailList->emails()->where('status', 'cross_duplicate');

        if ($bulkAction) {
            if (!empty($selectedIds)) {
                $query->whereIn('id', $selectedIds);
            }
            $emailsToResolve = $query->get();
            foreach ($emailsToResolve as $email) {
                $resolutions[$email->id] = $bulkAction;
            }
        } else {
            $emailsToResolve = $emailList->emails()
                ->whereIn('id', array_keys($resolutions))
                ->where('status', 'cross_duplicate')
                ->get();
        }

        if ($emailsToResolve->isEmpty()) {
            return redirect()->route('admin.email-lists.show', $emailList)
                ->with('success', 'No duplicates to resolve.');
        }

        DB::transaction(function () use ($resolutions, $emailsToResolve, $emailList) {
            foreach ($emailsToResolve as $email) {
                $action = $resolutions[$email->id] ?? null;
                if (!$action) continue;

                if ($action === 'keep_old') {
                    // Keep in old list only: delete from new list
                    $email->delete();
                } 
                elseif ($action === 'move_new') {
                    // Move to new list: remove from old lists, make valid in new list
                    $crossListDuplicates = $email->meta['cross_list_duplicates'] ?? [];
                    foreach ($crossListDuplicates as $dup) {
                        $oldListId = $dup['list_id'];
                        // Delete from the old list
                        $oldQuery = Email::where('email_list_id', $oldListId);
                        if (!empty($email->email)) {
                            $oldQuery->where('email', $email->email);
                        } else {
                            $oldQuery->where('whatsapp_number', $email->whatsapp_number);
                        }
                        $oldQuery->delete();
                        
                        $oldList = EmailList::find($oldListId);
                        $oldList?->recalculateStats();
                    }

                    // Mark as valid in current list
                    $email->update([
                        'status' => 'valid',
                        'subscription_status' => 'subscribed',
                    ]);
                } 
                elseif ($action === 'keep_both') {
                    // Keep in both: make valid in current list, leave in old lists
                    $email->update([
                        'status' => 'valid',
                        'subscription_status' => 'subscribed',
                    ]);
                }
            }

            // Recalculate stats for the current list
            $emailList->recalculateStats();
        });

        return redirect()->route('admin.email-lists.show', $emailList)
            ->with('success', 'Duplicates resolved successfully.');
    }

    public function transferContact(Request $request, EmailList $emailList, int $emailId)
    {
        $request->validate([
            'target_list_id' => 'required|exists:email_lists,id',
        ]);

        $targetList = EmailList::findOrFail($request->target_list_id);

        // Visibility check
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'Source list is private.');
            }
            if (!$targetList->is_public && $targetList->created_by_id !== $teamUserId) {
                abort(403, 'Target list is private.');
            }
        }

        // Action permissions check
        if (!$emailList->canPerformAction('delete_contact')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to remove contacts from this list.'], 403);
        }
        if (!$targetList->canPerformAction('add_contact')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to add contacts to the target list.'], 403);
        }

        $email = $emailList->emails()->findOrFail($emailId);

        // Move contact
        $email->update([
            'email_list_id' => $targetList->id,
        ]);

        // Recalculate stats
        $emailList->recalculateStats();
        $targetList->recalculateStats();

        return response()->json(['success' => true]);
    }

    public function sendToPipeline(Request $request, EmailList $emailList, int $emailId)
    {
        $request->validate([
            'pipeline_id' => 'required|exists:pipelines,id',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'title' => 'required|string|max:255',
            'value' => 'nullable|numeric|min:0',
        ]);

        $pipeline = \App\Models\Pipeline::findOrFail($request->pipeline_id);

        // Visibility check
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'List is private.');
            }
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                abort(403, 'Pipeline is private.');
            }
        }

        // Action permissions check
        if (!$pipeline->canPerformAction('add_deal')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to add deals to this pipeline.'], 403);
        }

        $email = $emailList->emails()->findOrFail($emailId);

        // Create deal
        \App\Models\Deal::create([
            'pipeline_stage_id' => $request->pipeline_stage_id,
            'email_id' => $email->id,
            'title' => $request->title,
            'value' => $request->value ?? 0,
            'currency' => 'INR',
            'status' => 'open',
            'user_id' => auth()->id(), // admin user id
            'order' => \App\Models\Deal::where('pipeline_stage_id', $request->pipeline_stage_id)->count(),
        ]);

        return response()->json(['success' => true]);
    }
}
