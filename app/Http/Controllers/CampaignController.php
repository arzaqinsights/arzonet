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
                $q->where('user_id', auth()->id());
            });
        }

        $campaigns = $query->paginate(15);

        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        // Create an initial draft to track progress
        $campaign = Campaign::create([
            'name' => 'Untitled Campaign ' . now()->format('Y-m-d H:i'),
            'status' => 'draft',
        ]);

        return redirect()->route('admin.campaigns.wizard', $campaign);
    }

    public function wizard(Campaign $campaign)
    {
        if ($campaign->status !== 'draft') {
            return redirect()->route('admin.campaigns.show', $campaign);
        }

        $emailLists = EmailList::where('status', 'completed')
            ->withCount(['emails as emails_count' => function ($query) {
                $query->valid()->subscribed();
            }])
            ->get();
        
        // Fetch all unique tags from the emails table
        $allTags = \DB::table('emails')
            ->whereNotNull('tags')
            ->distinct()
            ->pluck('tags')
            ->flatMap(fn($t) => is_string($t) ? json_decode($t, true) : $t)
            ->unique()
            ->filter()
            ->values();

        // Fetch unique segments
        $allSegments = \DB::table('emails')
            ->whereNotNull('segment_name')
            ->distinct()
            ->pluck('segment_name')
            ->filter()
            ->values();

        $templates = Template::all();
        $senders = Sender::all();

        return view('campaigns.wizard', compact('campaign', 'emailLists', 'templates', 'senders', 'allTags', 'allSegments'));
    }

    public function saveStep(Request $request, Campaign $campaign)
    {
        $data = $request->only(['name', 'subject', 'email_list_id', 'template_id', 'sender_id', 'scheduled_at', 'audience_config']);
        
        $data = array_filter($data, fn($value) => !is_null($value));

        $campaign->update($data);

        $sampleContact = null;
        $personalizedSubject = $campaign->subject;
        
        if ($campaign->email_list_id) {
            $list = \App\Models\EmailList::find($campaign->email_list_id);
            if ($list) {
                $sampleContact = $list->emails()->valid()->first();
                if ($sampleContact) {
                    $personalizer = app(\App\Services\PersonalizationService::class);
                    $personalizedSubject = $personalizer->preview($campaign->subject, $sampleContact->toArray());
                }
            }
        }

        return response()->json([
            'success' => true,
            'campaign' => $campaign,
            'sample_contact' => $sampleContact,
            'personalized_subject' => $personalizedSubject
        ]);
    }

    public function show(Campaign $campaign, CampaignService $campaignService, CostEstimationService $costService)
    {
        $campaign->load(['emailList', 'template']);
        $stats = $campaignService->getStats($campaign);
        $estimatedCost = $costService->campaignCost($campaign->total_recipients);

        // If draft, show expected recipients. If sending/completed, show logs.
        if ($campaign->status === 'draft') {
            $recentLogs = $campaign->emailList->emails()
                ->valid()
                ->subscribed()
                ->latest()
                ->take(20)
                ->get()
                ->map(function($email) {
                    // Map Email model to look like an EmailLog for the view
                    return (object) [
                        'email' => $email,
                        'email_address' => $email->email,
                        'status' => 'pending',
                        'error_message' => 'Waiting for launch...',
                        'sent_at' => null,
                        'created_at' => null
                    ];
                });
        } else {
            $recentLogs = $campaign->logs()
                ->latest()
                ->take(20)
                ->get();
        }

        return view('campaigns.show', compact('campaign', 'stats', 'estimatedCost', 'recentLogs'));
    }

    public function send(Campaign $campaign, CampaignService $campaignService)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'Campaign cannot be sent in its current state.');
        }

        $campaignService->dispatch($campaign);

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign is now sending! Mission launched.');
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

    public function report(Campaign $campaign)
    {
        $campaign->load(['emailList', 'template', 'sender']);
        
        $stats = [
            'total'         => $campaign->total_recipients,
            'sent'          => $campaign->sent_count,
            'failed'        => $campaign->failed_count,
            'opens'         => $campaign->activities()->where('type', 'opened')->count(),
            'unique_opens'  => $campaign->activities()->where('type', 'opened')->distinct('email_id')->count(),
            'clicks'        => $campaign->activities()->where('type', 'clicked')->count(),
            'unique_clicks' => $campaign->activities()->where('type', 'clicked')->distinct('email_id')->count(),
            'unsubscribes'  => $campaign->unsubscribes()->count(),
            'bounces'       => $campaign->logs()->where('status', 'bounced')->count(),
            'complaints'    => $campaign->logs()->where('status', 'complaint')->count(),
        ];

        // Provider-wise breakdown
        $providerStats = $campaign->logs()
            ->join('senders', 'email_logs.sender_id', '=', 'senders.id')
            ->select('senders.type as provider', 'senders.email as sender_email')
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when email_logs.status = "sent" or email_logs.status = "delivered" then 1 end) as sent')
            ->selectRaw('count(case when email_logs.status = "bounced" or email_logs.status = "failed" then 1 end) as failed')
            ->selectRaw('sum(open_count) as total_opens')
            ->selectRaw('sum(click_count) as total_clicks')
            ->groupBy('senders.type', 'senders.email')
            ->get();

        // Top clicked links
        $topLinks = $campaign->activities()
            ->where('type', 'clicked')
            ->select('url')
            ->selectRaw('count(*) as count')
            ->groupBy('url')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        $logs = $campaign->logs()->with('email')->latest()->paginate(50);

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

        $subjectSource = $request->subject ?: $template->subject;
        $previewSubject = $personalizer->preview($subjectSource ?? '', $sampleData);

        return response()->json([
            'html' => $previewHtml,
            'subject' => $previewSubject,
            'total_recipients' => $emailList->emails()->valid()->subscribed()->count()
        ]);
    }



    public function checkStatus(Campaign $campaign)
    {
        $openCount = $campaign->logs()->where('open_count', '>', 0)->count();
        $clickCount = $campaign->logs()->where('click_count', '>', 0)->count();
        $delivered = max(1, $campaign->sent_count);

        return response()->json([
            'status'       => $campaign->status,
            'sent_count'   => $campaign->sent_count,
            'failed_count' => $campaign->failed_count,
            'bounce_count' => $campaign->bounce_count,
            'unsubscribe_count' => $campaign->unsubscribes()->count(),
            'open_count'   => $openCount,
            'click_count'  => $clickCount,
            'open_rate'    => round(($openCount / $delivered) * 100, 1),
            'click_rate'   => round(($clickCount / $delivered) * 100, 1),
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

        $emailLists = EmailList::where('status', 'completed')
            ->withCount(['emails as emails_count' => function ($query) {
                $query->valid()->subscribed();
            }])
            ->get();
        
        $allTags = \DB::table('emails')
            ->whereNotNull('tags')
            ->distinct()
            ->pluck('tags')
            ->flatMap(fn($t) => is_string($t) ? json_decode($t, true) : $t)
            ->unique()
            ->filter()
            ->values();

        $allSegments = \DB::table('emails')
            ->whereNotNull('segment_name')
            ->distinct()
            ->pluck('segment_name')
            ->filter()
            ->values();

        $templates = Template::all();
        $senders = Sender::all();

        return view('campaigns.wizard', compact('campaign', 'emailLists', 'templates', 'senders', 'allTags', 'allSegments'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        // For partial updates (like renaming via AJAX)
        if ($request->has('name') && count($request->all()) <= 2) { // name + maybe _token/method
            $request->validate(['name' => 'required|string|max:255']);
            $campaign->update(['name' => $request->name]);
        } else {
            // Full update from wizard/edit page
            $request->validate([
                'name'              => 'required|string|max:255',
                'email_list_id'     => 'required|exists:email_lists,id',
                'template_id'       => 'required|exists:templates,id',
                'sender_id'         => 'required|exists:senders,id',
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
