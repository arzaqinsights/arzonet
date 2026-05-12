<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
class WhatsAppWebhookController extends Controller
{

    public function __construct()
    {
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

        // Dispatch the job for background processing
        \App\Jobs\ProcessWhatsAppWebhookJob::dispatch($payload);

        return response('OK', 200);
    }
}
