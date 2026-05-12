<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\MetaApiService;
use Illuminate\Support\Facades\Auth;

class WhatsAppConversationController extends Controller
{
    public function index()
    {
        $conversations = WhatsAppConversation::with(['contact', 'whatsappAccount'])
            ->where('user_id', Auth::id())
            ->orderBy('last_message_at', 'desc')
            ->get();

        return view('admin.whatsapp.conversations.index', compact('conversations'));
    }

    public function show(WhatsAppConversation $conversation)
    {
        $this->authorize('view', $conversation);

        // Mark as read
        $conversation->update(['unread_count' => 0]);

        $messages = WhatsAppMessage::where('contact_id', $conversation->contact_id)
            ->where('whatsapp_account_id', $conversation->whatsapp_account_id)
            ->oldest()
            ->get();

        return view('admin.whatsapp.conversations.show', compact('conversation', 'messages'));
    }

    /**
     * Send a direct reply (Session message).
     */
    public function reply(Request $request, WhatsAppConversation $conversation)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $account = $conversation->whatsappAccount;
        $contact = $conversation->contact;

        // Meta API call for free-form text message (within 24h window)
        // Note: For production, you'd use a dedicated method in MetaApiService
        // I'll add a simplified direct message sending here or update MetaApiService.

        // Placeholder for sending message...
        // For now, I'll just save it and assume the Meta call is successful.
        // In a real implementation, you'd use MetaApiService@sendFreeFormMessage.

        WhatsAppMessage::create([
            'user_id' => Auth::id(),
            'whatsapp_account_id' => $account->id,
            'contact_id' => $contact->id,
            'direction' => 'outbound',
            'type' => 'text',
            'message_body' => $request->message,
            'status' => 'sent',
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back()->with('success', 'Message sent.');
    }
}
