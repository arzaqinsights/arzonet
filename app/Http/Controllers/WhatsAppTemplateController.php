<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsAppTemplate;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\MetaApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class WhatsAppTemplateController extends Controller
{
    protected MetaApiService $metaApi;

    public function __construct(MetaApiService $metaApi)
    {
        $this->metaApi = $metaApi;
    }

    public function index()
    {
        $templates = WhatsAppTemplate::where('user_id', Auth::id())->latest()->get();
        $accounts  = WhatsAppAccount::where('user_id', Auth::id())->where('status', 'active')->get();
        return view('admin.whatsapp.templates.index', compact('templates', 'accounts'));
    }

    public function create()
    {
        $accounts = WhatsAppAccount::where('user_id', Auth::id())->where('status', 'active')->get();
        return view('admin.whatsapp.templates.create', compact('accounts'));
    }

    /**
     * Submit a new template to Meta for approval.
     */
    public function store(Request $request)
    {
        $request->validate([
            'whatsapp_account_id' => 'required|exists:whatsapp_accounts,id',
            'name'                => 'required|string|regex:/^[a-z0-9_]+$/',
            'category'            => 'required|in:MARKETING,UTILITY,AUTHENTICATION',
            'language'            => 'required|string',
            'body'                => 'required|string',
        ]);

        $account = WhatsAppAccount::where('id', $request->whatsapp_account_id)
            ->where('user_id', Auth::id())->firstOrFail();

        // Build components array
        $components = [];

        if ($request->filled('header_text') || in_array($request->header_type, ['IMAGE', 'DOCUMENT', 'VIDEO'])) {
            $comp = ['type' => 'HEADER', 'format' => $request->header_type ?? 'TEXT'];
            if ($request->header_type === 'TEXT') {
                $comp['text'] = $request->header_text;
            }
            $components[] = $comp;
        }

        $components[] = ['type' => 'BODY', 'text' => $request->body];

        if ($request->filled('footer_text')) {
            $components[] = ['type' => 'FOOTER', 'text' => $request->footer_text];
        }

        if ($request->buttons) {
            $btns = [];
            foreach ($request->buttons as $btn) {
                if (empty($btn['text'])) continue;
                $b = ['type' => $btn['type'], 'text' => $btn['text']];
                if ($btn['type'] === 'URL')          $b['url']          = $btn['url'] ?? '';
                if ($btn['type'] === 'PHONE_NUMBER') $b['phone_number'] = $btn['phone_number'] ?? '';
                $btns[] = $b;
            }
            if (!empty($btns)) $components[] = ['type' => 'BUTTONS', 'buttons' => $btns];
        }

        try {
            $accessToken = Crypt::decryptString($account->access_token);

            $payload = [
                'name'       => $request->name,
                'category'   => $request->category,
                'language'   => $request->language,
                'components' => $components,
            ];

            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post("https://graph.facebook.com/v22.0/{$account->whatsapp_business_account_id}/message_templates", $payload);

            Log::info('Meta template submission', ['status' => $response->status(), 'body' => $response->body()]);

            if ($response->failed()) {
                return back()->with('error', 'Meta rejected template: ' . ($response->json('error.message') ?? $response->body()));
            }

            $meta = $response->json();

            // Save to DB
            WhatsAppTemplate::create([
                'user_id'              => Auth::id(),
                'whatsapp_account_id'  => $account->id,
                'meta_template_id'     => $meta['id'] ?? null,
                'name'                 => $request->name,
                'category'             => $request->category,
                'language'             => $request->language,
                'status'               => strtolower($meta['status'] ?? 'pending'),
                'components'           => $components,
                'body'                 => $request->body,
            ]);

            return redirect()->route('admin.whatsapp.templates.index')
                ->with('success', 'Template submitted to Meta for approval successfully!');

        } catch (\Exception $e) {
            Log::error('Template submit error: ' . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Sync templates from Meta for a specific account.
     */
    public function sync(WhatsAppAccount $account)
    {
        // Ensure account belongs to current user
        if ($account->user_id !== Auth::id()) abort(403);

        try {
            $accessToken   = Crypt::decryptString($account->access_token);
            $metaTemplates = $this->metaApi->getTemplates(
                $account->whatsapp_business_account_id,
                $accessToken
            );

            $count = 0;
            foreach ($metaTemplates as $mt) {
                WhatsAppTemplate::updateOrCreate(
                    ['user_id' => Auth::id(), 'meta_template_id' => $mt['id']],
                    [
                        'whatsapp_account_id' => $account->id,
                        'name'                => $mt['name'],
                        'category'            => $mt['category'],
                        'language'            => $mt['language'],
                        'status'              => strtolower($mt['status']),
                        'components'          => $mt['components'],
                        'body'                => collect($mt['components'])->where('type', 'BODY')->first()['text'] ?? '',
                    ]
                );
                $count++;
            }

            return back()->with('success', "{$count} templates synced successfully from Meta.");
        } catch (\Exception $e) {
            Log::error('Template sync error: ' . $e->getMessage());
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
