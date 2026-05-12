<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsAppTemplate;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\MetaApiService;
use Illuminate\Support\Facades\Auth;

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
        return view('admin.whatsapp.templates.index', compact('templates'));
    }

    /**
     * Sync templates from Meta for a specific account.
     */
    public function sync(WhatsAppAccount $account)
    {
        $this->authorize('view', $account);

        try {
            $metaTemplates = $this->metaApi->getTemplates(
                $account->whatsapp_business_account_id,
                $account->access_token
            );

            foreach ($metaTemplates as $mt) {
                WhatsAppTemplate::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'meta_template_id' => $mt['id'],
                    ],
                    [
                        'whatsapp_account_id' => $account->id,
                        'name' => $mt['name'],
                        'category' => $mt['category'],
                        'language' => $mt['language'],
                        'status' => strtolower($mt['status']),
                        'components' => $mt['components'],
                        'body' => collect($mt['components'])->where('type', 'BODY')->first()['text'] ?? '',
                    ]
                );
            }

            return back()->with('success', count($metaTemplates) . ' templates synced successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
