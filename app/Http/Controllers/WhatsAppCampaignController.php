<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppTemplate;
use App\Jobs\ProcessWhatsAppCampaign;
use Illuminate\Support\Facades\Auth;

class WhatsAppCampaignController extends Controller
{
    public function index()
    {
        $query = WhatsAppCampaign::where('user_id', Auth::id());
        $campaigns = $query->latest()->get();
        return view('admin.whatsapp.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $activeWorkspaceId = session('last_opened_list_id');
        $templates = WhatsAppTemplate::where('user_id', Auth::id())->where('status', 'approved')->get();
        
        $emailListsQuery = \App\Models\EmailList::forWhatsApp()->where('status', 'completed');
        if ($activeWorkspaceId) {
            $emailListsQuery->where('id', $activeWorkspaceId);
        }
        $emailLists = $emailListsQuery->get();
        
        return view('admin.whatsapp.campaigns.create', compact('templates', 'emailLists'));
    }

    public function store(Request $request)
    {
        $activeWorkspaceId = session('last_opened_list_id');
        $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp_template_id' => 'required|exists:whatsapp_templates,id',
            'email_list_id' => 'nullable|exists:email_lists,id',
        ]);

        $campaign = WhatsAppCampaign::create([
            'user_id' => Auth::id(),
            'whatsapp_template_id' => $request->whatsapp_template_id,
            'email_list_id' => $request->email_list_id ?: $activeWorkspaceId,
            'name' => $request->name,
            'status' => 'draft',
        ]);

        return redirect()->route('admin.whatsapp.campaigns.index')->with('success', 'Campaign created.');
    }

    public function send(WhatsAppCampaign $campaign)
    {
        if ($campaign->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        
        $campaign->update(['status' => 'processing']);
        ProcessWhatsAppCampaign::dispatch($campaign->id);

        return back()->with('success', 'Campaign started.');
    }
}
