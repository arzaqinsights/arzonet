<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\EmailList;
use App\Models\Template;
use App\Models\Sender;
use App\Services\CampaignService;
use App\Services\PersonalizationService;
use App\Services\CostEstimationService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        $query = Campaign::with(['emailList', 'template', 'sender'])
            ->latest();

        if (auth()->check() && !auth()->user()->isAdmin()) {
            $query->whereHas('sender', function ($q) {
                $q->where('user_id', auth()->user()->getOwnerId());
            });
        }

        $campaigns = $query->paginate(15);

        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $activeWorkspaceId = session('last_opened_list_id');
        // Create an initial draft to track progress
        $campaign = Campaign::create([
            'name' => 'Untitled Campaign ' . now()->format('Y-m-d H:i'),
            'status' => 'draft',
            'email_list_id' => $activeWorkspaceId,
        ]);

        return redirect()->route('admin.campaigns.wizard', $campaign);
    }

    public function wizard(Campaign $campaign)
    {
        if ($campaign->status !== 'draft') {
            return redirect()->route('admin.campaigns.show', $campaign);
        }

        $activeWorkspaceId = session('last_opened_list_id') ?: $campaign->email_list_id;
        
        // Ensure campaign has list id if not set but workspace is active
        if (!$campaign->email_list_id && $activeWorkspaceId) {
            $campaign->email_list_id = $activeWorkspaceId;
            $campaign->save();
        }

        if ($activeWorkspaceId) {
            $topicsCount = \App\Models\SubscriptionTopic::where('email_list_id', $activeWorkspaceId)->count();
            if ($topicsCount === 0) {
                $emailList = \App\Models\EmailList::find($activeWorkspaceId);
                if ($emailList) {
                    \App\Models\SubscriptionTopic::seedDefaultsFor($activeWorkspaceId, $emailList->user_id);
                }
            }
        }
        $subscriptionTopics = \App\Models\SubscriptionTopic::where('email_list_id', $activeWorkspaceId)->get();

        $emailLists = EmailList::forEmail()
            ->where('status', 'completed')
            ->withCount(['emails as emails_count' => function ($query) {
                $query->valid()->subscribed()->whereNotNull('email')->where('email', '!=', '');
            }])
            ->get();
        
        // Fetch all unique tags from the emails table for the active list
        $allTags = \DB::table('emails')
            ->where('email_list_id', $activeWorkspaceId)
            ->whereNotNull('tags')
            ->distinct()
            ->pluck('tags')
            ->flatMap(fn($t) => is_string($t) ? json_decode($t, true) : $t)
            ->unique()
            ->filter()
            ->values();

        // Fetch unique segments for the active list
        $staticSegments = \DB::table('emails')
            ->where('email_list_id', $activeWorkspaceId)
            ->whereNotNull('segment_name')
            ->distinct()
            ->pluck('segment_name')
            ->filter()
            ->values()
            ->toArray();

        $dynamicSegments = \App\Models\Segment::where(function ($q) use ($activeWorkspaceId) {
                $q->whereNull('email_list_id')
                  ->orWhere('email_list_id', $activeWorkspaceId);
            })
            ->pluck('name')
            ->toArray();

        $allSegments = array_values(array_unique(array_merge($staticSegments, $dynamicSegments)));

        $templates = Template::all();
        $senders = Sender::all();
        $verifiedDomains = \App\Models\VerifiedDomain::where('status', 'verified')->get();

        return view('campaigns.wizard', compact('campaign', 'emailLists', 'templates', 'senders', 'allTags', 'allSegments', 'subscriptionTopics', 'verifiedDomains'));
    }



    public function show(Request $request, Campaign $campaign, CampaignService $campaignService, CostEstimationService $costService)
    {
        $campaign->load(['emailList', 'template', 'sender']);
        $stats = $campaignService->getStats($campaign);
        $estimatedCost = $costService->campaignCost($campaign->total_recipients);

        // Top clicked links
        $topLinks = \App\Models\EmailEvent::whereHas('log', function($q) use ($campaign) {
                $q->where('campaign_id', $campaign->id);
            })
            ->where('type', 'click')
            ->whereNotNull('url')
            ->where('url', 'NOT LIKE', '%admin.arzonet.com/t/c/%')
            ->where('url', 'NOT LIKE', '%unsubscribe%')
            ->select('url')
            ->selectRaw('count(*) as count')
            ->selectRaw('count(distinct email_log_id) as unique_count')
            ->groupBy('url')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // Device Analytics (User Agent parsing)
        $deviceStats = \App\Models\EmailEvent::whereHas('log', function($q) use ($campaign) {
                $q->where('campaign_id', $campaign->id);
            })
            ->whereIn('type', ['open', 'click'])
            ->selectRaw("
                SUM(CASE WHEN LOWER(user_agent) LIKE '%mobile%' OR LOWER(user_agent) LIKE '%android%' OR LOWER(user_agent) LIKE '%iphone%' THEN 1 ELSE 0 END) as mobile_count,
                SUM(CASE WHEN LOWER(user_agent) NOT LIKE '%mobile%' AND LOWER(user_agent) NOT LIKE '%android%' AND LOWER(user_agent) NOT LIKE '%iphone%' THEN 1 ELSE 0 END) as desktop_count
            ")
            ->first();

        $totalDevices = ($deviceStats->mobile_count ?? 0) + ($deviceStats->desktop_count ?? 0);
        $desktopPercent = $totalDevices > 0 ? round((($deviceStats->desktop_count ?? 0) / $totalDevices) * 100, 1) : 0;
        $mobilePercent = $totalDevices > 0 ? round((($deviceStats->mobile_count ?? 0) / $totalDevices) * 100, 1) : 0;

        // Top IPs for GeoLocation
        $topIps = \App\Models\EmailEvent::whereHas('log', function($q) use ($campaign) {
                $q->where('campaign_id', $campaign->id);
            })
            ->whereNotNull('ip_address')
            ->select('ip_address')
            ->selectRaw('count(*) as count')
            ->groupBy('ip_address')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // If draft, show expected recipients. If sending/completed, show logs.
        if ($campaign->status === 'draft') {
            $query = $campaign->emailList 
                ? $campaign->emailList->emails()->valid()->subscribed()->whereNotNull('email')->where('email', '!=', '') 
                : null;
            
            if ($request->search && $query) {
                $query->where('email', 'LIKE', "%{$request->search}%");
            }

            $logs = $query 
                ? $query->latest()
                    ->paginate(50)
                    ->through(function($email) {
                        return (object) [
                            'email' => $email,
                            'email_address' => $email->email,
                            'status' => 'pending',
                            'error_message' => 'Waiting for launch...',
                            'message_id' => null,
                            'sent_at' => null,
                            'created_at' => null
                        ];
                    })
                : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
        } else {
            $query = $campaign->logs()->with('email');

            // --- Filtering ---
            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->engagement === 'opened') {
                $query->where(function($q) {
                    $q->where('open_count', '>', 0)->orWhere('click_count', '>', 0);
                });
            } elseif ($request->engagement === 'clicked') {
                $query->where('click_count', '>', 0);
            }

            if ($request->search) {
                $query->where('email_address', 'LIKE', "%{$request->search}%");
            }

            // --- Sorting ---
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $allowedSorts = ['status', 'created_at', 'open_count', 'click_count', 'email_address'];
            
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir);
            } else {
                $query->latest();
            }

            $logs = $query->paginate(50)->withQueryString();
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('campaigns.partials.logs_table', compact('logs'))->render(),
                'pagination' => (string) $logs->links()
            ]);
        }

        return view('campaigns.show', compact('campaign', 'stats', 'estimatedCost', 'logs', 'topLinks', 'topIps', 'desktopPercent', 'mobilePercent'));
    }

    public function exportLogs(Campaign $campaign, Request $request)
    {
        $query = $campaign->logs()->with('email');

        // Apply filters
        if ($request->status) $query->where('status', $request->status);
        if ($request->engagement === 'opened') {
            $query->where(function($q) {
                $q->where('open_count', '>', 0)->orWhere('click_count', '>', 0);
            });
        } elseif ($request->engagement === 'clicked') {
            $query->where('click_count', '>', 0);
        }
        if ($request->search) $query->where('email_address', 'LIKE', "%{$request->search}%");

        // New: Export status filter
        if ($request->exported_filter === 'not_exported') {
            $query->where('is_exported', false);
        } elseif ($request->exported_filter === 'exported') {
            $query->where('is_exported', true);
        }

        // Get first log to determine meta headers
        $firstLog = (clone $query)->first();
        $metaKeys = [];
        if ($firstLog && $firstLog->email && $firstLog->email->meta) {
            $metaKeys = array_keys($firstLog->email->meta);
        }

        // Get format
        $ext = $request->input('format') === 'csv' ? 'csv' : 'xlsx';
        $filename = "campaign_{$campaign->id}_logs_" . now()->format('Y-m-d_H-i-s') . '.' . $ext;

        // Mark as exported
        $logIds = (clone $query)->pluck('id')->toArray();
        if (!empty($logIds)) {
            \App\Models\EmailLog::whereIn('id', $logIds)->update([
                'is_exported' => true,
                'exported_at' => now()
            ]);
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CampaignLogsExport($query, $metaKeys), 
            $filename
        );
    }

    public function send(Campaign $campaign, CampaignService $campaignService)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'Campaign cannot be sent in its current state.');
        }

        // Validate upfront (fast checks only)
        $usage = $campaign->user->getEmailsUsage();
        if ($usage->is_exceeded) {
            return back()->with('error', 'Email sending limit exceeded. Please upgrade your plan.');
        }
        if (!$campaign->emailList) {
            return back()->with('error', 'Campaign has no email list attached.');
        }
        if (!$campaign->sender) {
            return back()->with('error', 'Campaign has no sender configured.');
        }

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            $campaign->update(['status' => 'scheduled']);
            return redirect()->route('admin.campaigns.index')
                ->with('success', 'Campaign scheduled successfully for ' . $campaign->scheduled_at->format('Y-m-d H:i') . '.');
        }

        $campaign->update(['status' => 'preparing']);
        \App\Jobs\PrepareCampaignDispatchJob::dispatch($campaign->id)->onQueue('high');

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign is being prepared for sending...');
    }

    public function pause(Campaign $campaign, CampaignService $campaignService)
    {
        if ($campaign->status !== 'sending') {
            return back()->with('error', 'Only sending campaigns can be paused.');
        }

        $campaignService->pause($campaign);

        return back()->with('success', 'Campaign paused.');
    }

    public function resume(Campaign $campaign, CampaignService $campaignService)
    {
        if ($campaign->status !== 'paused') {
            return back()->with('error', 'Only paused campaigns can be resumed.');
        }

        $campaignService->resume($campaign);

        return back()->with('success', 'Campaign resumed.');
    }

    public function cancel(Campaign $campaign, CampaignService $campaignService)
    {
        if (!in_array($campaign->status, ['sending', 'paused', 'scheduled'])) {
            return back()->with('error', 'Campaign cannot be cancelled.');
        }

        $campaignService->cancel($campaign);

        return back()->with('success', 'Campaign cancelled.');
    }

    public function retryFailed(Campaign $campaign, CampaignService $campaignService)
    {
        $campaignService->retryFailed($campaign);
        return back()->with('success', 'Retrying failed emails...');
    }

    public function report(Request $request, Campaign $campaign)
    {
        $campaign->load(['emailList', 'template', 'sender']);
        
        $logStats = \DB::table('email_logs')
            ->where('campaign_id', $campaign->id)
            ->selectRaw("
                COUNT(*) as total_logs,
                SUM(CASE WHEN status IN ('sent','delivered') THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced,
                SUM(CASE WHEN status IN ('complaint','spamreport') THEN 1 ELSE 0 END) as spam_reports,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocks,
                SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as drops,
                SUM(CASE WHEN status = 'deferred' THEN 1 ELSE 0 END) as deferred,
                SUM(CASE WHEN error_message LIKE '%invalid%' THEN 1 ELSE 0 END) as invalid,
                SUM(open_count) as total_opens,
                SUM(click_count) as total_clicks,
                SUM(CASE WHEN open_count > 0 OR click_count > 0 THEN 1 ELSE 0 END) as unique_opens,
                SUM(CASE WHEN click_count > 0 THEN 1 ELSE 0 END) as unique_clicks
            ")->first();

        $stats = [
            'total'         => $campaign->total_recipients,
            'sent'          => (int) $logStats->sent,
            'delivered'     => (int) $logStats->sent,
            'failed'        => $campaign->failed_count,
            'opens'         => (int) $logStats->total_opens,
            'unique_opens'  => (int) $logStats->unique_opens,
            'clicks'        => (int) $logStats->total_clicks,
            'unique_clicks' => (int) $logStats->unique_clicks,
            'unsubscribes'  => $campaign->unsubscribes()->count(),
            'bounces'       => (int) $logStats->bounced,
            'spam_reports'  => (int) $logStats->spam_reports,
            'blocks'        => (int) $logStats->blocks,
            'drops'         => (int) $logStats->drops,
            'invalid'       => (int) $logStats->invalid,
            'deferred'      => (int) $logStats->deferred,
        ];

        // Provider-wise breakdown (Fixed JOIN on campaigns.sender_id instead of email_logs.sender_id)
        $providerStats = \DB::table('email_logs')
            ->where('email_logs.campaign_id', $campaign->id)
            ->join('campaigns', 'email_logs.campaign_id', '=', 'campaigns.id')
            ->join('senders', 'campaigns.sender_id', '=', 'senders.id')
            ->select('senders.type as provider', 'senders.email as sender_email')
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when email_logs.status = "sent" or email_logs.status = "delivered" then 1 end) as sent')
            ->selectRaw('count(case when email_logs.status = "bounced" or email_logs.status = "failed" then 1 end) as failed')
            ->selectRaw('sum(open_count) as total_opens')
            ->selectRaw('sum(click_count) as total_clicks')
            ->groupBy('senders.type', 'senders.email')
            ->get();

        // Top clicked links
        $topLinks = \App\Models\EmailEvent::whereHas('log', function($q) use ($campaign) {
                $q->where('campaign_id', $campaign->id);
            })
            ->where('type', 'click')
            ->whereNotNull('url')
            ->where('url', 'NOT LIKE', '%admin.arzonet.com/t/c/%')
            ->where('url', 'NOT LIKE', '%unsubscribe%')
            ->select('url')
            ->selectRaw('count(*) as count')
            ->groupBy('url')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        $logsQuery = $campaign->logs()->with('email')->latest();

        if ($request->exported_filter === 'not_exported') {
            $logsQuery->where('is_exported', false);
        } elseif ($request->exported_filter === 'exported') {
            $logsQuery->where('is_exported', true);
        }

        $logs = $logsQuery->paginate(50)->withQueryString();

        return view('campaigns.report', compact('campaign', 'stats', 'logs', 'topLinks', 'providerStats'));
    }

    public function preview(Request $request, PersonalizationService $personalizer)
    {
        $template = Template::findOrFail($request->template_id);
        $emailList = EmailList::findOrFail($request->email_list_id);

        $sampleEmail = $emailList->emails()->valid()->first();
        
        $sampleData = $sampleEmail ? [
            'name'  => $sampleEmail->name ?? 'Recipient',
            'email' => $sampleEmail->email,
            'meta'  => is_array($sampleEmail->meta) ? $sampleEmail->meta : json_decode($sampleEmail->meta ?? '[]', true),
            'unsubscribe_url' => url('/unsubscribe/sample'),
        ] : null;

        $previewHtml = $personalizer->preview($template->html_content ?? '', $sampleData);
        
        // Failsafe: If preview is empty, use raw content
        if (empty($previewHtml)) {
            $previewHtml = $template->html_content;
        }

        $subjectSource = $request->subject;
        $previewSubject = $personalizer->preview($subjectSource ?? '', $sampleData);

        return response()->json([
            'html' => $previewHtml,
            'subject' => $previewSubject,
            'total_recipients' => $emailList->emails()->valid()->subscribed()->count()
        ]);
    }



    public function checkStatus(Campaign $campaign, CampaignService $campaignService)
    {
        $stats = $campaignService->getStats($campaign);
        $delivered = max(1, $stats['sent']);

        return response()->json([
            'status'       => $campaign->status,
            'sent_count'   => $stats['sent'],
            'failed_count' => $stats['failed'],
            'bounce_count' => $stats['bounced'],
            'dropped_count'=> $stats['dropped'],
            'blocked_count'=> $stats['blocked'] ?? 0,
            'spam_count'   => $stats['spam'],
            'unsubscribe_count' => $stats['unsubscribed'],
            'total_opens'  => $stats['opens'],
            'unique_opens' => $stats['unique_opens'],
            'total_clicks' => $stats['clicks'],
            'unique_clicks'=> $stats['unique_clicks'],
            'open_rate'    => round(($stats['unique_opens'] / $delivered) * 100, 1),
            'click_rate'   => round(($stats['unique_clicks'] / $delivered) * 100, 1),
            'total'        => $campaign->total_recipients,
            'progress'     => $campaign->progress(),
            'speed'        => $campaign->currentSpeed(),
            'eta'          => $campaign->estimatedCompletion(),
            'recent_logs'  => $campaign->logs()->latest()->take(10)->get()->map(function($log) {
                return [
                    'email_address' => $log->email_address,
                    'status' => $log->status,
                    'message_id' => $log->message_id,
                    'sent_at' => $log->sent_at ? $log->sent_at->format('H:i:s') : '—',
                ];
            }),
        ]);
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    public function edit(Campaign $campaign)
    {
        // Only allow editing if not completed or currently sending
        if (in_array($campaign->status, ['completed', 'sending'])) {
            return redirect()->route('admin.campaigns.index')->with('error', 'Active or completed campaigns cannot be edited. Try duplicating instead.');
        }

        $activeWorkspaceId = session('last_opened_list_id') ?: $campaign->email_list_id;

        $emailLists = EmailList::where('status', 'completed')
            ->withCount(['emails as emails_count' => function ($query) {
                $query->valid()->subscribed();
            }])
            ->get();
        
        $allTags = \DB::table('emails')
            ->where('email_list_id', $activeWorkspaceId)
            ->whereNotNull('tags')
            ->distinct()
            ->pluck('tags')
            ->flatMap(fn($t) => is_string($t) ? json_decode($t, true) : $t)
            ->unique()
            ->filter()
            ->values();

        $staticSegments = \DB::table('emails')
            ->where('email_list_id', $activeWorkspaceId)
            ->whereNotNull('segment_name')
            ->distinct()
            ->pluck('segment_name')
            ->filter()
            ->values()
            ->toArray();

        $dynamicSegments = \App\Models\Segment::where(function ($q) use ($activeWorkspaceId) {
                $q->whereNull('email_list_id')
                  ->orWhere('email_list_id', $activeWorkspaceId);
            })
            ->pluck('name')
            ->toArray();

        $allSegments = array_values(array_unique(array_merge($staticSegments, $dynamicSegments)));

        if ($activeWorkspaceId) {
            $topicsCount = \App\Models\SubscriptionTopic::where('email_list_id', $activeWorkspaceId)->count();
            if ($topicsCount === 0) {
                $emailList = \App\Models\EmailList::find($activeWorkspaceId);
                if ($emailList) {
                    \App\Models\SubscriptionTopic::seedDefaultsFor($activeWorkspaceId, $emailList->user_id);
                }
            }
        }
        $subscriptionTopics = \App\Models\SubscriptionTopic::where('email_list_id', $activeWorkspaceId)->get();

        $templates = Template::all();
        $senders = Sender::all();
        $verifiedDomains = \App\Models\VerifiedDomain::where('status', 'verified')->get();

        return view('campaigns.wizard', compact('campaign', 'emailLists', 'templates', 'senders', 'allTags', 'allSegments', 'subscriptionTopics', 'verifiedDomains'));
    }

    public function saveStep(Request $request, Campaign $campaign)
    {
        $data = $request->only(['name', 'from_name', 'from_email', 'subject', 'email_list_id', 'template_id', 'sender_id', 'scheduled_at', 'audience_config', 'subscription_topic_id']);
        
        // If from_email is provided but sender_id is not, find or assign logic happens in wizard front-end
        // We want to allow null values for subscription_topic_id to allow resetting it
        $cleanedData = [];
        foreach ($data as $key => $val) {
            if ($key === 'subscription_topic_id') {
                $cleanedData[$key] = $val ?: null;
            } elseif ($key === 'from_name' || $key === 'from_email') {
                $cleanedData[$key] = $val ?: null;
            } elseif (!is_null($val)) {
                $cleanedData[$key] = $val;
            }
        }

        // Auto-inject workspace email list id if empty
        if (empty($cleanedData['email_list_id'])) {
            $cleanedData['email_list_id'] = session('last_opened_list_id');
        }

        $fromEmail = $request->input('from_email');
        if ($fromEmail) {
            $parts = explode('@', $fromEmail);
            if (count($parts) === 2) {
                $domain = strtolower($parts[1]);
                $verifiedDomain = \App\Models\VerifiedDomain::where('domain', $domain)
                    ->where('status', 'verified')
                    ->first();

                if ($verifiedDomain) {
                    $sender = \App\Models\Sender::where('email', $fromEmail)
                        ->where('user_id', $campaign->user_id ?: auth()->id())
                        ->first();

                    if (!$sender) {
                        $sender = \App\Models\Sender::create([
                            'user_id' => $campaign->user_id ?: auth()->id(),
                            'email' => $fromEmail,
                            'status' => 'verified',
                            'type' => 'ses', // default provider
                            'from_name' => $request->input('from_name') ?: ($campaign->from_name ?: auth()->user()->name),
                            'verified_at' => now(),
                            'verified_domain_id' => $verifiedDomain->id,
                        ]);
                    }
                    $cleanedData['sender_id'] = $sender->id;
                } else {
                    $cleanedData['sender_id'] = null;
                }
            }
        } else {
            $cleanedData['sender_id'] = null;
        }

        $campaign->update($cleanedData);

        $sampleContact = null;
        $personalizedSubject = $campaign->subject;
        $query = $campaign->getAudienceQueryBuilder();
        if ($query) {
            $sampleContact = $query->first();
            if ($sampleContact) {
                $personalizer = app(\App\Services\PersonalizationService::class);
                $personalizedSubject = $personalizer->preview($campaign->subject ?? '', $sampleContact->toArray());
            }
        }

        $estimatedRecipients = $campaign->getEstimatedRecipientCount();

        return response()->json([
            'success' => true,
            'campaign' => $campaign,
            'sample_contact' => $sampleContact,
            'personalized_subject' => $personalizedSubject,
            'estimated_recipients' => $estimatedRecipients
        ]);
    }

    public function update(Request $request, Campaign $campaign)
    {
        // For partial updates (like renaming via AJAX)
        if ($request->has('name') && !$request->has('email_list_id')) {
            $request->validate(['name' => 'required|string|max:255']);
            $campaign->update(['name' => $request->name]);
        } else {
            // Full update from wizard/edit page
            $request->validate([
                'name'              => 'required|string|max:255',
                'from_name'         => 'nullable|string|max:255',
                'from_email'        => 'nullable|email|max:255',
                'email_list_id'     => 'required|exists:email_lists,id',
                'template_id'       => 'nullable|exists:templates,id',
                'sender_id'         => 'nullable|exists:senders,id',
                'emails_per_minute' => 'nullable|integer|min:1',
            ]);
            $campaign->update($request->all());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'name' => $campaign->name
            ]);
        }

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign updated successfully.');
    }

    public function clone(Campaign $campaign)
    {
        $newCampaign = $campaign->replicate();
        $newCampaign->name = $campaign->name . ' (Copy)';
        $newCampaign->status = 'draft';
        $newCampaign->sent_count = 0;
        $newCampaign->failed_count = 0;
        $newCampaign->started_at = null;
        $newCampaign->completed_at = null;
        $newCampaign->save();

        return redirect()->route('admin.campaigns.edit', $newCampaign)->with('success', 'Campaign duplicated! You can now adjust and launch it.');
    }

    public function sendTest(Request $request, Campaign $campaign, CampaignService $campaignService)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $campaignService->sendTestEmail($campaign, $request->email);
            return back()->with('success', 'Test email sent to ' . $request->email);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }
}
