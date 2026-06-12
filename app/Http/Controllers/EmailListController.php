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
    public function switchWorkspace($id)
    {
        $query = EmailList::query();
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            $query->where(function($q) use ($teamUserId) {
                $q->where('is_public', true)
                  ->orWhere('created_by_id', $teamUserId);
            });
        }
        
        $workspace = $query->findOrFail($id);
        
        session(['last_opened_list_id' => $workspace->id]);
        
        $previousUrl = url()->previous();
        
        // If they were on an email list specific page, redirect to the new workspace's dashboard
        if (\Illuminate\Support\Str::contains($previousUrl, '/email-lists')) {
            return redirect()->route('admin.email-lists.show', $workspace->id);
        }
        
        return redirect()->back();
    }

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

        $lists = $query->orderBy('name')->paginate(10);

        // Sum up pre-calculated stats from lists
        $totalReach = 0;
        $totalValid = 0;
        $totalInvalid = 0;
        $totalDuplicate = 0;
        
        foreach ($query->get() as $list) {
            $totalReach += $list->total_records;
            $totalValid += $list->valid_count;
            $totalInvalid += $list->invalid_count;
            $totalDuplicate += $list->duplicate_count;
        }

        $listIds = $query->pluck('id')->toArray();
        if (!empty($listIds)) {
            $globalSubscribed = \App\Models\Email::whereIn('email_list_id', $listIds)->where('is_archived', false)->where('subscription_status', 'subscribed')->count();
            $globalBounced = \App\Models\Email::whereIn('email_list_id', $listIds)->where('is_archived', false)->where('subscription_status', 'bounced')->count();
        } else {
            $globalSubscribed = 0;
            $globalBounced = 0;
        }

        $globalStats = [
            'total' => $totalReach,
            'subscribed' => $globalSubscribed,
            'bounced' => $globalBounced,
            'invalid' => $totalInvalid,
        ];

        return view('email-lists.index', compact('lists', 'globalStats'));
    }

    public function create()
    {
        if (!\App\Models\User::canAccess('workspace.create')) {
            abort(403, 'Unauthorized action. You do not have permission to create workspaces.');
        }
        return view('email-lists.create');
    }

    /**
     * Store a new email list.
     * FLOW: Upload -> Store File -> Return Mapping Preview
     */
    public function store(Request $request, FileParserService $parser)
    {
        if (!\App\Models\User::canAccess('workspace.create')) {
            abort(403, 'Unauthorized action. You do not have permission to create workspaces.');
        }
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
        ]);

        return redirect()->route('admin.email-lists.import-settings', $emailList);
    }

    public function show(EmailList $emailList)
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list is private.');
            }
        }

        session(['last_opened_list_id' => $emailList->id]);

        if (empty($emailList->signup_form_token)) {
            $emailList->signup_form_token = \Illuminate\Support\Str::random(32);
            $emailList->save();
        }

        $stats = $emailList->getStatistics();

        // Match Alpine.js default filter: Active Only (is_archived = false)
        // simplePaginate avoids COUNT(*) on millions of rows — only checks for next page
        $emails = $emailList->emails()->with(['deals.stage.pipeline', 'user'])->where('is_archived', false)->orderBy('created_at', 'desc')->simplePaginate(50);
        // Skip loadDynamicSegments on initial load — uses stored segment_name column instead

        // Cache filters (segments, tags, sources, addedBy) in Redis with 24h TTL
        $filterCacheKey = "list_filters:{$emailList->id}";
        $cachedFilters = \Illuminate\Support\Facades\Redis::get($filterCacheKey);
        
        if ($cachedFilters) {
            $decodedFilters = json_decode($cachedFilters, true);
            $segments = $decodedFilters['segments'] ?? [];
            $tags = $decodedFilters['tags'] ?? [];
            $sources = $decodedFilters['sources'] ?? [];
            $addedByUserIds = $decodedFilters['added_by_user_ids'] ?? [];
        } else {
            $dbSegments = \App\Models\Segment::where(function($q) use ($emailList) {
                $q->whereNull('email_list_id')->orWhere('email_list_id', $emailList->id);
            })->pluck('name')->toArray();
            $dbSegments = array_filter($dbSegments, function($name) {
                return !str_starts_with($name, 'Auto: ');
            });
            $customSegments = $emailList->emails()->whereNotNull('segment_name')->distinct()->pluck('segment_name')->toArray();
            $customSegments = array_filter($customSegments, function($name) {
                return !str_starts_with($name, 'Auto: ');
            });
            $segments = array_values(array_unique(array_merge($dbSegments, $customSegments)));
            
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
            $addedByUserIds = $emailList->emails()->whereNotNull('user_id')->distinct()->pluck('user_id')->toArray();

            $decodedFilters = [
                'segments' => $segments,
                'tags' => $tags,
                'sources' => $sources,
                'added_by_user_ids' => $addedByUserIds
            ];

            \Illuminate\Support\Facades\Redis::setex($filterCacheKey, 86400, json_encode($decodedFilters));
        }

        $addedByOptions = \App\Models\User::whereIn('id', $addedByUserIds)->pluck('name', 'id')->toArray();

        $topics = \App\Models\SubscriptionTopic::where('email_list_id', $emailList->id)->get();

        $pipelines = \App\Models\Pipeline::with('stages')->get();
        $destinationListsQuery = \App\Models\EmailList::where('id', '!=', $emailList->id);
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            $destinationListsQuery->where(function($q) use ($teamUserId) {
                $q->where('is_public', true)
                  ->orWhere('created_by_id', $teamUserId);
            });
        }
        $destinationLists = $destinationListsQuery->get();
        $sequencesList = \App\Models\Sequence::where('email_list_id', $emailList->id)->get();

        return view('email-lists.show', compact('emailList', 'stats', 'emails', 'segments', 'tags', 'sources', 'topics', 'addedByOptions', 'pipelines', 'destinationLists', 'sequencesList'));
    }

    public function optOutAnalytics(EmailList $emailList)
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list is private.');
            }
        }

        $cacheKey = "opt_out_stats:{$emailList->id}";
        $isProcessing = $emailList->status === 'processing';
        $cacheTtl = $isProcessing ? 2 : 86400;

        $cached = \Illuminate\Support\Facades\Redis::get($cacheKey);
        if ($cached) {
            $stats = json_decode($cached, true);
            if ($stats) {
                return response()->json($stats);
            }
        }

        $listTopics = \App\Models\SubscriptionTopic::where('email_list_id', $emailList->id)->get();
        $topicStats = [];

        foreach ($listTopics as $topic) {
            $subCount = \App\Models\Email::where('email_list_id', $emailList->id)
                ->where('subscription_status', 'subscribed')
                ->whereJsonContains('subscribed_topics', (string) $topic->id)
                ->count();

            $unsubCount = \App\Models\Email::where('email_list_id', $emailList->id)
                ->where('subscription_status', 'unsubscribed')
                ->whereJsonContains('subscribed_topics', (string) $topic->id)
                ->count();

            $total = $subCount + $unsubCount;
            $unsubRate = $total > 0 ? round(($unsubCount / $total) * 100, 1) : 0;

            $topicStats[] = [
                'name' => $topic->name,
                'description' => $topic->description,
                'sub_count' => $subCount,
                'unsub_count' => $unsubCount,
                'unsub_rate' => $unsubRate,
            ];
        }

        $totalUnsubscribed = \App\Models\Email::where('email_list_id', $emailList->id)->where('subscription_status', 'unsubscribed')->count();
        $totalContacts = \App\Models\Email::where('email_list_id', $emailList->id)->where('is_archived', false)->count();
        $overallOptOutRate = $totalContacts > 0 ? round(($totalUnsubscribed / $totalContacts) * 100, 1) : 0;

        $stats = [
            'topic_stats' => $topicStats,
            'total_unsubscribed' => $totalUnsubscribed,
            'total_contacts' => $totalContacts,
            'total_subscribers' => $totalContacts - $totalUnsubscribed,
            'overall_opt_out_rate' => $overallOptOutRate,
        ];

        \Illuminate\Support\Facades\Redis::setex($cacheKey, $cacheTtl, json_encode($stats));

        return response()->json($stats);
    }



    private function loadDynamicSegments($emails, $emailList)
    {
        $visibleIds = $emails->pluck('id')->toArray();
        if (empty($visibleIds)) return $emails;

        $allSegments = \App\Models\Segment::whereNull('email_list_id')->orWhere('email_list_id', $emailList->id)->get();
        $matchedSegments = [];
        foreach ($allSegments as $seg) {
            $matchedIds = \App\Models\Segment::applyRulesToQuery(\App\Models\Email::whereIn('id', $visibleIds), $seg->rules ?? [])->pluck('id');
            foreach ($matchedIds as $id) {
                $matchedSegments[$id][] = $seg->name;
            }
        }

        foreach ($emails as $email) {
            if (isset($matchedSegments[$email->id])) {
                $email->segment_name = implode(', ', $matchedSegments[$email->id]);
            } else {
                $email->segment_name = null;
            }
        }

        return $emails;
    }

    private function applyFiltersToQuery($query, Request $request, EmailList $emailList)
    {
        if ($request->added_by && $request->added_by !== 'all' && (!is_array($request->added_by) || count($request->added_by) > 0)) {
            $addedBy = is_array($request->added_by) ? $request->added_by : [$request->added_by];
            $query->whereIn('user_id', $addedBy);
        }

        if ($request->status && $request->status !== 'all' && (!is_array($request->status) || count($request->status) > 0)) {
            $statuses = is_array($request->status) ? $request->status : [$request->status];
            $query->where(function($q) use ($statuses) {
                foreach ($statuses as $st) {
                    if ($st === 'role_based') $q->orWhere('is_role_based', true);
                    elseif ($st === 'disposable') $q->orWhere('is_disposable', true);
                    elseif (in_array($st, ['risky', 'suspicious', 'cross_duplicate'])) $q->orWhere('email_status', $st);
                    else $q->orWhere('status', $st);
                }
            });
        }

        // Subscription status filter
        if ($request->subscription && $request->subscription !== 'all' && (!is_array($request->subscription) || count($request->subscription) > 0)) {
            $subs = is_array($request->subscription) ? $request->subscription : [$request->subscription];
            $query->where(function($q) use ($subs) {
                foreach ($subs as $sub) {
                    if (in_array($sub, ['hard_bounce', 'soft_bounce', 'complaint'])) {
                        $q->orWhere('email_status', $sub);
                    } else {
                        $q->orWhere('subscription_status', $sub);
                    }
                }
            });
        }

        // Archive filter
        if ($request->archived && $request->archived !== 'all' && (!is_array($request->archived) || count($request->archived) > 0)) {
            $archives = is_array($request->archived) ? $request->archived : [$request->archived];
            if (in_array('yes', $archives) && !in_array('no', $archives)) {
                $query->where('is_archived', true);
            } elseif (in_array('no', $archives) && !in_array('yes', $archives)) {
                $query->where('is_archived', false);
            }
        }

        // Segment filter
        if ($request->segment && $request->segment !== 'all' && (!is_array($request->segment) || count($request->segment) > 0)) {
            $segments = is_array($request->segment) ? $request->segment : [$request->segment];
            $query->where(function($q) use ($segments, $emailList) {
                foreach ($segments as $value) {
                    $segmentModel = \App\Models\Segment::where(function($sq) use ($emailList) {
                            $sq->whereNull('email_list_id')->orWhere('email_list_id', $emailList->id);
                        })->where('name', $value)->first();

                    if ($segmentModel) {
                        $q->orWhere(function($subQ) use ($segmentModel) {
                            \App\Models\Segment::applyRulesToQuery($subQ, $segmentModel->rules ?? []);
                        });
                    } else {
                        $q->orWhere('segment_name', $value);
                    }
                }
            });
        }

        // Topic filter
        if ($request->topic && $request->topic !== 'all' && (!is_array($request->topic) || count($request->topic) > 0)) {
            $topics = is_array($request->topic) ? $request->topic : [$request->topic];
            $query->where(function($q) use ($topics) {
                foreach ($topics as $t) {
                    $q->orWhereJsonContains('subscribed_topics', (string) $t);
                }
            });
        }

        // Source filter
        if ($request->source && $request->source !== 'all' && (!is_array($request->source) || count($request->source) > 0)) {
            $sources = is_array($request->source) ? $request->source : [$request->source];
            $query->whereIn('signup_source', $sources);
        }

        // Tag filter
        if ($request->tag && $request->tag !== 'all' && (!is_array($request->tag) || count($request->tag) > 0)) {
            $tags = is_array($request->tag) ? $request->tag : [$request->tag];
            $query->where(function($q) use ($tags) {
                foreach ($tags as $t) {
                    $q->orWhere('tags', 'like', "%{$t}%");
                }
            });
        }

        // Channel filter
        if ($request->channel && $request->channel !== 'all' && (!is_array($request->channel) || count($request->channel) > 0)) {
            $channels = is_array($request->channel) ? $request->channel : [$request->channel];
            if (in_array('only_email', $channels) && !in_array('only_whatsapp', $channels)) {
                $query->whereNotNull('email')->where('email', '!=', '');
            } elseif (in_array('only_whatsapp', $channels) && !in_array('only_email', $channels)) {
                $query->whereNotNull('whatsapp_number')->where('whatsapp_number', '!=', '');
            }
        }

        // WhatsApp Subscription Status filter
        if ($request->wa_status && $request->wa_status !== 'all' && (!is_array($request->wa_status) || count($request->wa_status) > 0)) {
            $waStatuses = is_array($request->wa_status) ? $request->wa_status : [$request->wa_status];
            $query->whereIn('whatsapp_subscription_status', $waStatuses);
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
                    $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}'))) LIKE ?", ["%" . strtolower($search) . "%"]);
                }
            });
        }
        // Advanced Rules (Dynamic Multi-Condition)
        if ($request->has('advanced_rules') && is_array($request->advanced_rules)) {
            foreach ($request->advanced_rules as $rule) {
                if (empty($rule['field']) || empty($rule['operator'])) continue;
                
                $field = $rule['field'];
                $operator = $rule['operator'];
                $value = $rule['value'] ?? '';

                $query->where(function ($q) use ($field, $operator, $value) {
                    $isCustom = str_starts_with($field, 'custom_');
                    $dbField = $isCustom ? "LOWER(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}')))" : "LOWER({$field})";
                    $dbValue = strtolower($value);

                    switch ($operator) {
                        case 'equals':
                            if ($isCustom) {
                                $q->whereRaw("$dbField = ?", [$dbValue]);
                            } else {
                                $q->where($field, 'LIKE', $value); // Case insensitive in MySQL usually, but fallback
                            }
                            break;
                        case 'not_equals':
                            if ($isCustom) {
                                $q->whereRaw("$dbField != ? OR JSON_EXTRACT(meta, '$.{$field}') IS NULL", [$dbValue]);
                            } else {
                                $q->where($field, 'NOT LIKE', $value)->orWhereNull($field);
                            }
                            break;
                        case 'contains':
                            $q->whereRaw("$dbField LIKE ?", ["%{$dbValue}%"]);
                            break;
                        case 'not_contains':
                            $q->whereRaw("$dbField NOT LIKE ? OR $dbField IS NULL", ["%{$dbValue}%"]);
                            break;
                        case 'starts_with':
                            $q->whereRaw("$dbField LIKE ?", ["{$dbValue}%"]);
                            break;
                        case 'ends_with':
                            $q->whereRaw("$dbField LIKE ?", ["%{$dbValue}"]);
                            break;
                        case 'is_empty':
                            if ($isCustom) {
                                $q->whereRaw("JSON_EXTRACT(meta, '$.{$field}') IS NULL OR $dbField = ''");
                            } else {
                                $q->whereNull($field)->orWhere($field, '');
                            }
                            break;
                        case 'is_not_empty':
                            if ($isCustom) {
                                $q->whereRaw("JSON_EXTRACT(meta, '$.{$field}') IS NOT NULL AND $dbField != ''");
                            } else {
                                $q->whereNotNull($field)->where($field, '!=', '');
                            }
                            break;
                        case 'greater_than':
                            if ($isCustom) {
                                $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}')) AS DECIMAL) > ?", [(float)$value]);
                            } else {
                                $q->where($field, '>', $value);
                            }
                            break;
                        case 'less_than':
                            if ($isCustom) {
                                $q->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.{$field}')) AS DECIMAL) < ?", [(float)$value]);
                            } else {
                                $q->where($field, '<', $value);
                            }
                            break;
                    }
                });
            }
        }

        return $query;
    }

    public function filterEmails(Request $request, EmailList $emailList)
    {
        $query = $emailList->emails()->with(['deals.stage.pipeline', 'user'])->orderBy('created_at', 'desc');

        $query = $this->applyFiltersToQuery($query, $request, $emailList);

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
            ($request->topic && $request->topic !== 'all') ||
            ($request->added_by && $request->added_by !== 'all') ||
            ($request->advanced_rules && !empty($request->advanced_rules)) ||
            $request->search) {
            $isFiltered = true;
        }

        $dynamicStats['is_filtered'] = $isFiltered;
        // Use simple COUNT(*) instead of expensive COUNT(DISTINCT CASE WHEN ...)
        $dynamicStats['filtered_main_rows'] = $isFiltered ? (int) ($dbStats->total ?? 0) : 0;

        // simplePaginate avoids COUNT(*) — only checks for next page
        $emails = $query->simplePaginate(50);
        $topics = \App\Models\SubscriptionTopic::where('email_list_id', $emailList->id)->get();
        return response()->json([
            'html' => view('email-lists.partials.email-table-rows', ['emails' => $emails, 'emailList' => $emailList, 'topics' => $topics])->render(),
            'links' => $emails->links()->toHtml(),
            'stats' => $dynamicStats,
            'global_stats' => $globalStats
        ]);
    }

    public function exportContacts(Request $request, EmailList $emailList)
    {
        $query = $emailList->emails()->orderBy('created_at', 'desc');

        $query = $this->applyFiltersToQuery($query, $request, $emailList);

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

        if (session('last_opened_list_id') == $emailList->id) {
            session()->forget('last_opened_list_id');
        }

        $emailList->delete();
        return redirect()->route('admin.email-lists.index')->with('success', 'Email list deleted successfully.');
    }

    public function checkStatus(EmailList $emailList)
    {
        $stats = $emailList->getStatistics();

        $activeBulkActionLog = $emailList->activityLogs()
            ->where('type', 'bulk_action')
            ->where('details->status', 'started')
            ->latest()
            ->first();

        $lastCompletedBulkAction = $emailList->activityLogs()
            ->where('type', 'bulk_action')
            ->where('details->status', 'completed')
            ->where('updated_at', '>=', now()->subSeconds(15))
            ->latest()
            ->first();

        $data = [
            'status' => $emailList->status,
            'active_bulk_action' => $activeBulkActionLog ? $activeBulkActionLog->details : null,
            'last_bulk_action_completed' => !is_null($lastCompletedBulkAction),
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
            'signup_source', 'subscription_status', 'whatsapp_subscription_status', 'tags', 'subscribed_topics'
        ]);

        if (array_key_exists('tags', $data)) {
            if (is_string($data['tags'])) {
                $tagsArray = array_map('trim', array_filter(explode(',', $data['tags'])));
                $data['tags'] = !empty($tagsArray) ? array_values($tagsArray) : null;
            } elseif (is_array($data['tags'])) {
                $data['tags'] = array_values(array_filter($data['tags']));
            }
        }

        if ($request->has('subscribed_topics')) {
            $topics = $request->input('subscribed_topics');
            $data['subscribed_topics'] = is_array($topics) ? $topics : [];
        }

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
            'action' => 'required|in:archive,unarchive,unsubscribe,subscribe,permanent_delete,edit_column,update_column,add_tags,remove_tags,manage_subscriptions,create_deals,transfer,add_note,add_task,enroll_sequence'
        ]);

        if ($request->global && $request->filters) {
            $query = clone $emailList->emails();
            
            // Create a pseudo-request using the filters array to reuse applyFiltersToQuery
            $filterRequest = new Request($request->filters);
            $query = $this->applyFiltersToQuery($query, $filterRequest, $emailList);

            $emails = clone $query;
        } else {
            $emails = $emailList->emails()->whereIn('id', $request->ids);
        }

        $count = $emails->count();

        // All bulk actions except permanent_delete
        $advancedActions = [
            'add_tags', 'remove_tags', 'manage_subscriptions', 'create_deals', 
            'transfer', 'add_note', 'add_task', 'unsubscribe', 'subscribe', 
            'archive', 'unarchive', 'edit_column', 'update_column', 'enroll_sequence'
        ];

        if (in_array($request->action, $advancedActions)) {
            $payload = $request->payload ?? [];
            
            // Map extra parameters for specific actions to the payload
            if ($request->action === 'unsubscribe') {
                $payload['duration'] = $request->input('duration', 'forever');
            } elseif (in_array($request->action, ['update_column', 'edit_column'])) {
                $payload['column'] = $request->input('column') ?? $request->input('target_column');
                $payload['value'] = $request->input('value') ?? $request->input('new_value');
            }

            $emailList->update(['status' => 'processing']);

            $activityLog = $emailList->activityLogs()->create([
                'user_id' => auth()->id(),
                'type' => 'bulk_action',
                'details' => [
                    'action' => $request->action,
                    'status' => 'started',
                    'count' => $count,
                    'scope' => $request->global ? 'global' : 'selection',
                    'filters' => $request->global ? $request->filters : null,
                    'payload' => $payload
                ]
            ]);

            \App\Jobs\AdvancedBulkActionJob::dispatch(
                $emailList->id,
                $request->action,
                $request->global ? true : false,
                $request->global ? $request->filters : null,
                $request->global ? [] : $request->ids,
                $payload,
                auth()->id(),
                $activityLog->id
            );

            return response()->json([
                'success' => true,
                'message' => 'The bulk action process has started in the background. It will be completed shortly.'
            ]);
        }

        if ($request->action === 'permanent_delete' || $request->action === 'delete') {
            $reason = $request->input('delete_reason', $request->input('reason', 'User requested permanent deletion'));
            
            $emailList->update(['status' => 'processing']);

            $activityLog = $emailList->activityLogs()->create([
                'user_id' => auth()->id(),
                'type' => 'bulk_action',
                'details' => [
                    'action' => 'permanent_delete',
                    'status' => 'started',
                    'count' => $count,
                    'scope' => $request->global ? 'global' : 'selection',
                    'filters' => $request->global ? $request->filters : null
                ]
            ]);

            \App\Jobs\BulkPermanentDeleteJob::dispatch(
                $emailList->id,
                $request->global ? true : false,
                $request->global ? $request->filters : null,
                $request->global ? [] : $request->ids,
                $reason,
                $activityLog->id
            );

            return response()->json([
                'success' => true,
                'message' => 'The permanent delete process has started in the background. Depending on the size, it may take a few minutes.'
            ]);
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

        $targetList = EmailList::withoutGlobalScopes()->findOrFail($request->target_list_id);

        // Visibility check
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                return response()->json(['success' => false, 'message' => 'Source list is private.'], 403);
            }
            if (!$targetList->is_public && $targetList->created_by_id !== $teamUserId) {
                return response()->json(['success' => false, 'message' => 'Target list is private.'], 403);
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

        // Fetch target list's topics or seed defaults if none exist
        $targetTopicIds = \App\Models\SubscriptionTopic::withoutGlobalScopes()
            ->where('email_list_id', $targetList->id)
            ->pluck('id')
            ->map('strval')
            ->toArray();

        if (empty($targetTopicIds)) {
            \App\Models\SubscriptionTopic::seedDefaultsFor($targetList->id, $targetList->user_id);
            $targetTopicIds = \App\Models\SubscriptionTopic::withoutGlobalScopes()
                ->where('email_list_id', $targetList->id)
                ->pluck('id')
                ->map('strval')
                ->toArray();
        }

        // Fetch all associated emails in the same group sharing original_row_id
        $emailsToTransfer = collect([$email]);
        if (!empty($email->original_row_id)) {
            $subRows = $emailList->emails()
                ->where('original_row_id', $email->original_row_id)
                ->where('id', '!=', $email->id)
                ->get();
            $emailsToTransfer = $emailsToTransfer->merge($subRows);
        }

        // Move contact and alternate channels
        foreach ($emailsToTransfer as $e) {
            $e->update([
                'email_list_id' => $targetList->id,
                'user_id' => $targetList->user_id,
                'subscribed_topics' => $targetTopicIds,
                'meta' => [], // reset custom columns
            ]);
        }

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
                return response()->json(['success' => false, 'message' => 'List is private.'], 403);
            }
            if (!$pipeline->is_public && $pipeline->created_by_id !== $teamUserId) {
                return response()->json(['success' => false, 'message' => 'Pipeline is private.'], 403);
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

    public function updateSettings(Request $request, EmailList $emailList)
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if ($emailList->created_by_id !== $teamUserId && !$emailList->canPerformAction('edit')) {
                abort(403, 'Unauthorized.');
            }
        }

        $emailList->update([
            'double_opt_in' => $request->has('double_opt_in'),
        ]);

        return back()->with('success', 'List settings updated successfully.');
    }

    // ──────────────────────────────────────────────────────
    // Contact Profile & Merge
    // ──────────────────────────────────────────────────────
    public function getProfile(EmailList $emailList, $emailId)
    {
        $email = $emailList->emails()->with(['user', 'activities', 'notes' => function($q) {
            $q->with('user')->latest();
        }, 'tasks' => function($q) {
            $q->latest();
        }, 'sequenceEnrollments.sequence'])->findOrFail($emailId);

        return response()->json($email);
    }

    public function addNote(Request $request, EmailList $emailList, $emailId)
    {
        $request->validate(['content' => 'required|string']);
        $email = $emailList->emails()->findOrFail($emailId);
        
        $note = \App\Models\ContactNote::create([
            'email_id' => $email->id,
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        return response()->json(['success' => true, 'note' => $note->load('user')]);
    }

    public function addTask(Request $request, EmailList $emailList, $emailId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);
        
        $email = $emailList->emails()->findOrFail($emailId);
        
        $task = \App\Models\ContactTask::create([
            'email_id' => $email->id,
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
        ]);

        return response()->json(['success' => true, 'task' => $task]);
    }

    public function mergeDuplicates(Request $request, EmailList $emailList)
    {
        $request->validate([
            'merge_by' => 'required|in:email,whatsapp',
        ]);

        $field = $request->merge_by === 'email' ? 'email' : 'whatsapp_number';

        // Find duplicates
        $duplicates = \Illuminate\Support\Facades\DB::table('emails')
            ->select($field)
            ->where('email_list_id', $emailList->id)
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->groupBy($field)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($field);

        if ($duplicates->isEmpty()) {
            return response()->json(['success' => true, 'message' => 'No duplicates found to merge.']);
        }

        $mergedCount = 0;

        foreach ($duplicates as $duplicateValue) {
            $contacts = $emailList->emails()->where($field, $duplicateValue)->orderBy('updated_at', 'desc')->get();
            if ($contacts->count() < 2) continue;

            $master = $contacts->first();
            $duplicatesToMerge = $contacts->slice(1);

            foreach ($duplicatesToMerge as $dup) {
                // Merge tags
                $masterTags = $master->tags ?? [];
                $dupTags = $dup->tags ?? [];
                $master->tags = array_values(array_unique(array_merge($masterTags, $dupTags)));

                // Merge topics
                $masterTopics = $master->subscribed_topics ?? [];
                $dupTopics = $dup->subscribed_topics ?? [];
                $master->subscribed_topics = array_values(array_unique(array_merge($masterTopics, $dupTopics)));

                // Merge null fields
                $fillableFields = [
                    'name', 'whatsapp_number', 'email', 'status', 'email_status', 'email_score', 
                    'whatsapp_subscription_status', 'subscription_status', 'last_active_at', 'last_engaged_at'
                ];
                foreach ($fillableFields as $f) {
                    if (empty($master->$f) && !empty($dup->$f)) {
                        $master->$f = $dup->$f;
                    }
                }

                $master->save();

                // Re-assign relationships
                \App\Models\ContactActivity::where('email_id', $dup->id)->update(['email_id' => $master->id]);
                \App\Models\ContactNote::where('email_id', $dup->id)->update(['email_id' => $master->id]);
                \App\Models\ContactTask::where('email_id', $dup->id)->update(['email_id' => $master->id]);
                \App\Models\Deal::where('email_id', $dup->id)->update(['email_id' => $master->id]);

                // Delete duplicate
                $dup->delete();
                $mergedCount++;
            }
        }

        $emailList->recalculateStats();

        return response()->json(['success' => true, 'message' => "Merged $mergedCount duplicate records successfully."]);
    }

    public function showImportSettings(EmailList $emailList)
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list is private.');
            }
        }

        // Fetch list-specific subscription topics
        $topics = \App\Models\SubscriptionTopic::where('email_list_id', $emailList->id)->get();

        // Fetch existing tags in the workspace/list to recommend them to the user
        $tagsCollection = Email::where('email_list_id', $emailList->id)
            ->whereNotNull('tags')
            ->pluck('tags');

        $uniqueTags = collect($tagsCollection)
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return view('email-lists.import-settings', compact('emailList', 'topics', 'uniqueTags'));
    }

    public function startImport(Request $request, EmailList $emailList)
    {
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            if (!$emailList->is_public && $emailList->created_by_id !== $teamUserId) {
                abort(403, 'This list is private.');
            }
        }

        $request->validate([
            'topics' => 'nullable|array',
            'tags' => 'nullable|array',
            'new_tags' => 'nullable|string',
        ]);

        $selectedTopicIds = array_map('intval', $request->input('topics', []));

        // Gather tags
        $checkboxTags = $request->input('tags', []);
        $newTagsRaw = $request->input('new_tags', '');
        $newTagsArray = array_map('trim', array_filter(explode(',', $newTagsRaw)));
        $selectedTags = array_values(array_unique(array_merge($checkboxTags, $newTagsArray)));

        $emailList->update([
            'status' => 'processing',
        ]);

        $log = \App\Models\ActivityLog::create([
            'email_list_id' => $emailList->id,
            'user_id' => auth()->id(),
            'type' => 'import',
            'details' => [
                'status' => 'started',
                'source' => $emailList->original_filename,
                'tags' => $selectedTags,
                'topics' => $selectedTopicIds,
                'processed' => 0,
                'valid' => 0,
                'invalid' => 0,
                'duplicate' => 0
            ]
        ]);

        ProcessEmailListJob::dispatch($emailList->id, $log->id, $selectedTags, $selectedTopicIds);

        return redirect()
            ->route('admin.email-lists.show', $emailList)
            ->with('success', 'CRM Import started. Contacts will appear shortly.');
    }

    public function ajaxCreateTopic(Request $request, EmailList $emailList)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $topic = \App\Models\SubscriptionTopic::create([
            'user_id' => auth()->id(),
            'email_list_id' => $emailList->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'topic' => [
                'id' => $topic->id,
                'name' => $topic->name,
            ]
        ]);
    }

    public function ajaxDeleteTopic(Request $request, EmailList $emailList, \App\Models\SubscriptionTopic $topic)
    {
        if ($topic->email_list_id !== $emailList->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $topic->delete();

        return response()->json(['success' => true]);
    }

    public function ajaxDeleteTag(Request $request, EmailList $emailList)
    {
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

        return response()->json(['success' => true]);
    }
}
