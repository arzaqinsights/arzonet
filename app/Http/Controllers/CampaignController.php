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
        $emailLists = EmailList::where('status', 'completed')
            ->where('valid_count', '>', 0)
            ->withCount(['emails as active_count' => function ($query) {
                $query->valid()->subscribed();
            }])
            ->get();

        $templates = Template::all();
        
        $sendersQuery = Sender::query();
        if (auth()->check() && !auth()->user()->isAdmin()) {
            $sendersQuery->where('user_id', auth()->id());
        }
        $senders = $sendersQuery->get();

        return view('campaigns.create', compact('emailLists', 'templates', 'senders'));
    }

    public function store(Request $request, CostEstimationService $costService)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'email_list_id'     => 'required|exists:email_lists,id',
            'template_id'       => 'required|exists:templates,id',
            'sender_id'         => 'required|exists:senders,id',
            'emails_per_minute' => 'nullable|integer|min:1|max:1000',
            'batch_size'        => 'nullable|integer|min:10|max:500',
            'scheduled_at'      => 'nullable|date|after:now',
        ]);

        $emailList = EmailList::findOrFail($request->email_list_id);
        
        // Calculate eligible recipients (Valid + Subscribed)
        $unsubscribedEmails = \App\Models\Unsubscribe::pluck('email')->toArray();
        $eligibleCount = $emailList->emails()
            ->valid()
            ->subscribed()
            ->whereNotIn('email', $unsubscribedEmails)
            ->count();

        $campaign = Campaign::create([
            'name'              => $request->name,
            'email_list_id'     => $request->email_list_id,
            'template_id'       => $request->template_id,
            'sender_id'         => $request->sender_id,
            'status'            => $request->scheduled_at ? 'scheduled' : 'draft',
            'scheduled_at'      => $request->scheduled_at,
            'total_recipients'  => $eligibleCount,
            'emails_per_minute' => $request->emails_per_minute ?? config('emailplatform.limits.emails_per_minute'),
            'batch_size'        => $request->batch_size ?? config('emailplatform.batch_size'),
        ]);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign, CampaignService $campaignService, CostEstimationService $costService)
    {
        $campaign->load(['emailList', 'template']);
        $stats = $campaignService->getStats($campaign);
        $estimatedCost = $costService->campaignCost($campaign->total_recipients);

        // If draft, show expected recipients. If sending/completed, show logs.
        if ($campaign->status === 'draft') {
            $unsubscribedEmails = \App\Models\Unsubscribe::pluck('email')->toArray();
            $recentLogs = $campaign->emailList->emails()
                ->valid()
                ->subscribed()
                ->whereNotIn('email', $unsubscribedEmails)
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

        return back()->with('success', 'Campaign is now sending!');
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
        ];

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

        return view('campaigns.report', compact('campaign', 'stats', 'logs', 'topLinks'));
    }

    public function preview(Request $request, PersonalizationService $personalizer)
    {
        $template = Template::findOrFail($request->template_id);
        $emailList = EmailList::findOrFail($request->email_list_id);

        $sampleEmail = $emailList->emails()->valid()->first();
        $sampleData = $sampleEmail ? [
            'name'  => $sampleEmail->name ?? 'John Doe',
            'email' => $sampleEmail->email,
            'meta'  => $sampleEmail->meta ?? [],
        ] : null;

        $previewHtml = $personalizer->preview($template->html_content, $sampleData);
        $previewSubject = $personalizer->preview($template->subject, $sampleData);

        return response()->json([
            'html'              => $previewHtml,
            'total_recipients'  => $emailList->valid_count,
            'subject'           => $previewSubject,
        ]);
    }

    public function checkStatus(Campaign $campaign)
    {
        return response()->json([
            'status'      => $campaign->status,
            'sent_count'  => $campaign->sent_count,
            'failed_count'=> $campaign->failed_count,
            'total'       => $campaign->total_recipients,
            'progress'    => $campaign->progress(),
        ]);
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }
}
