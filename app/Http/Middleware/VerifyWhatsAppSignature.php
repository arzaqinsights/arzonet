<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip verification for GET (Meta Challenge)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return response('No signature', 403);
        }

        $appSecret = config('services.whatsapp.app_secret');
        $payload = $request->getContent();
        
        // Remove 'sha256=' prefix
        $expectedSignature = hash_hmac('sha256', $payload, $appSecret);
        $actualSignature = str_replace('sha256=', '', $signature);

        if (!hash_equals($expectedSignature, $actualSignature)) {
            \Illuminate\Support\Facades\Log::warning('WhatsApp Webhook Invalid Signature detected.');
            return response('Invalid signature', 403);
        }

        return $next($request);
    }
}
