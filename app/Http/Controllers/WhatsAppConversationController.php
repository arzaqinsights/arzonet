<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\MetaApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class WhatsAppConversationController extends Controller
{
    public function index()
    {
        $conversations = WhatsAppConversation::with(['contact', 'whatsappAccount', 'agent'])
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

        $conversations = WhatsAppConversation::with(['contact', 'whatsappAccount', 'agent'])
            ->where('user_id', Auth::id())
            ->orderBy('last_message_at', 'desc')
            ->get();

        $messages = WhatsAppMessage::where('contact_id', $conversation->contact_id)
            ->where('whatsapp_account_id', $conversation->whatsapp_account_id)
            ->oldest()
            ->get();

        return view('admin.whatsapp.conversations.index', compact('conversations', 'conversation', 'messages'));
    }

    /**
     * Send a direct reply (Session message).
     */
    public function reply(Request $request, WhatsAppConversation $conversation, MetaApiService $metaApi)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $account = $conversation->whatsappAccount;
        $contact = $conversation->contact;

        try {
            $accessToken = Crypt::decryptString($account->access_token);
            
            $response = $metaApi->sendFreeFormMessage(
                $account->phone_number_id,
                $accessToken,
                $contact->whatsapp_number,
                $request->message
            );

            $waId = $response['messages'][0]['id'] ?? null;

            if ($waId) {
                WhatsAppMessage::create([
                    'user_id' => Auth::id(),
                    'whatsapp_account_id' => $account->id,
                    'contact_id' => $contact->id,
                    'wa_message_id' => $waId,
                    'direction' => 'outbound',
                    'type' => 'text',
                    'message_body' => $request->message,
                    'status' => 'sent',
                ]);

                $conversation->update([
                    'last_message_at' => now(),
                    'last_message_preview' => \Illuminate\Support\Str::limit($request->message, 100),
                ]);

                return back()->with('success', 'Message sent.');
            } else {
                throw new \Exception('Failed to get message ID from Meta.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send message: ' . $e->getMessage());
        }
    }
}
