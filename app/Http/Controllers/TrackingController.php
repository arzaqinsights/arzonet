<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\Email;
use App\Jobs\ProcessTrackingEventJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    /**
     * Handle Email Open (Pixel)
     */
    public function open(Request $request, string $token)
    {
        Log::info("Tracking Open Request: token={$token}, IP=" . $request->ip());
        $log = EmailLog::where('tracking_token', $token)->first();
        
        if ($log) {
            ProcessTrackingEventJob::dispatch($log->id, 'open', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ]);
        } else {
            Log::warning("Tracking Open Failed: Invalid Token {$token}");
        }

        // Return 1x1 transparent PNG
        $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        
        return response($pixel)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'private, no-cache, no-cache=Set-Cookie, proxy-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
    }

    /**
     * Handle Link Click
     */
    public function click(Request $request, string $token)
    {
        $url = base64_decode($request->query('url'));
        Log::info("Tracking Click Request: token={$token}, URL={$url}, IP=" . $request->ip());
        $log = EmailLog::where('tracking_token', $token)->first();

        if ($log) {
            ProcessTrackingEventJob::dispatch($log->id, 'click', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
                'url' => $url
            ]);
        } else {
            Log::warning("Tracking Click Failed: Invalid Token {$token}");
        }

        return redirect()->away($url ?: '/');
    }

    public function unsubscribe(Request $request, string $token)
    {
        $log = EmailLog::where('tracking_token', $token)->first();

        if ($log && $log->email) {
            $email = $log->email;
            $secureToken = hash_hmac('sha256', $email->id . $email->email, config('app.key'));

            return redirect()->route('unsubscribe.show', [
                'id' => $email->id,
                'token' => $secureToken,
                'lid' => $log->id
            ]);
        }

        return abort(404);
    }
}
