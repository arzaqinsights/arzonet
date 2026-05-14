<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppTemplate;
use App\Models\Email as Contact;
use App\Services\WhatsApp\MetaApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WhatsAppConversationController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $conversations = WhatsAppConversation::with(['contact', 'whatsappAccount', 'agent'])
            ->where('user_id', Auth::id())
            ->orderBy('last_message_at', 'desc')
            ->get();

        $accounts  = WhatsAppAccount::where('user_id', Auth::id())->where('status', 'active')->get();
        $templates = WhatsAppTemplate::where('user_id', Auth::id())->where('status', 'approved')->get();

        return view('admin.whatsapp.conversations.index', compact('conversations', 'accounts', 'templates'));
    }

    public function show(WhatsAppConversation $conversation)
    {
        $this->authorize('view', $conversation);
        $conversation->update(['unread_count' => 0]);

        $conversations = WhatsAppConversation::with(['contact', 'whatsappAccount', 'agent'])
            ->where('user_id', Auth::id())->orderBy('last_message_at', 'desc')->get();

        $messages  = WhatsAppMessage::where('contact_id', $conversation->contact_id)
            ->where('whatsapp_account_id', $conversation->whatsapp_account_id)->oldest()->get();

        $accounts  = WhatsAppAccount::where('user_id', Auth::id())->where('status', 'active')->get();
        $templates = WhatsAppTemplate::where('user_id', Auth::id())->where('status', 'approved')->get();

        return view('admin.whatsapp.conversations.index', compact('conversations', 'conversation', 'messages', 'accounts', 'templates'));
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

    /**
     * Initiate a new conversation using an approved template.
     */
    public function initiate(Request $request, MetaApiService $metaApi)
    {
        $request->validate([
            'whatsapp_account_id' => 'required|exists:whatsapp_accounts,id',
            'template_id'         => 'required|exists:whatsapp_templates,id',
            'phone_number'        => 'required|string',
        ]);

        $account  = WhatsAppAccount::where('id', $request->whatsapp_account_id)->where('user_id', Auth::id())->firstOrFail();
        $template = WhatsAppTemplate::where('id', $request->template_id)->where('user_id', Auth::id())->where('status', 'approved')->firstOrFail();

        // Normalize: remove all non-numeric characters
        $to = preg_replace('/[^0-9]/', '', $request->phone_number);
        
        // Remove leading zero if present (common in manual entry)
        if (str_starts_with($to, '0')) {
            $to = substr($to, 1);
        }

        // If exactly 10 digits remain, assume it's an Indian number without a code
        if (strlen($to) === 10) {
            $to = '91' . $to;
        }

        try {
            $accessToken = Crypt::decryptString($account->access_token);

            $response = $metaApi->sendTemplateMessage(
                $account->phone_number_id,
                $accessToken,
                $to,
                $template->name,
                $template->language
            );

            $waId = $response['messages'][0]['id'] ?? null;

            if (!$waId) {
                throw new \Exception($response['error']['message'] ?? 'Meta did not return a message ID.');
            }

            // Find or create a default "WhatsApp Contacts" list for this user
            $list = \App\Models\EmailList::firstOrCreate(
                ['user_id' => Auth::id(), 'name' => 'WhatsApp Contacts'],
                ['description' => 'Automatically created list for WhatsApp conversations']
            );

            // Find or create contact
            $contact = Contact::firstOrCreate(
                ['user_id' => Auth::id(), 'whatsapp_number' => $to],
                [
                    'email_list_id' => $list->id,
                    'email' => $to . '@whatsapp.com', 
                    'name' => $to, 
                    'subscription_status' => 'subscribed'
                ]
            );

            DB::transaction(function () use ($account, $contact, $waId, $template) {
                WhatsAppMessage::create([
                    'user_id'             => Auth::id(),
                    'whatsapp_account_id' => $account->id,
                    'contact_id'          => $contact->id,
                    'wa_message_id'       => $waId,
                    'direction'           => 'outbound',
                    'type'                => 'template',
                    'message_body'        => $template->body,
                    'status'              => 'sent',
                ]);

                WhatsAppConversation::updateOrCreate(
                    ['whatsapp_account_id' => $account->id, 'contact_id' => $contact->id],
                    [
                        'user_id'              => Auth::id(),
                        'last_message_at'      => now(),
                        'last_message_preview' => \Illuminate\Support\Str::limit($template->body, 100),
                        'unread_count'         => 0,
                    ]
                );
            });

            return back()->with('success', "Template message sent to {$to} successfully!");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to initiate conversation: ' . $e->getMessage());
        }
    }
}
