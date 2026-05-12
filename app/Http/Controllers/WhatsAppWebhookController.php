<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsApp\WebhookProcessor;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected WebhookProcessor $processor;

    public function __construct(WebhookProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Handle the verification challenge from Meta.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === config('services.whatsapp.webhook_verify_token')) {
                return response($challenge, 200);
            }
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhook data.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Log for debugging (optional in production)
        Log::info("WhatsApp Webhook Payload: ", $payload);

        try {
            $this->processor->process($payload);
            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error("WhatsApp Webhook Error: " . $e->getMessage());
            return response('Error', 500);
        }
    }
}
