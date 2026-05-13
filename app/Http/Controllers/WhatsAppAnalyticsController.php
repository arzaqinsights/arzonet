<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Auth;

class WhatsAppAnalyticsController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $stats = [
            'sent'           => WhatsAppMessage::where('user_id', $userId)->where('direction', 'outbound')->count(),
            'delivered'      => WhatsAppMessage::where('user_id', $userId)->where('direction', 'outbound')->where('status', 'delivered')->count(),
            'read'           => WhatsAppMessage::where('user_id', $userId)->where('direction', 'outbound')->where('status', 'read')->count(),
            'failed'         => WhatsAppMessage::where('user_id', $userId)->where('direction', 'outbound')->where('status', 'failed')->count(),
            'inbound'        => WhatsAppMessage::where('user_id', $userId)->where('direction', 'inbound')->count(),
            'conversations'  => WhatsAppConversation::where('user_id', $userId)->count(),
            'templates_used' => WhatsAppTemplate::where('user_id', $userId)->where('status', 'approved')->count(),
            'campaigns'      => WhatsAppCampaign::where('user_id', $userId)->count(),
        ];

        $recentConversations = WhatsAppConversation::where('user_id', $userId)
            ->with('contact')
            ->latest('last_message_at')
            ->take(10)
            ->get();

        return view('admin.whatsapp.analytics', compact('stats', 'recentConversations'));
    }
}
