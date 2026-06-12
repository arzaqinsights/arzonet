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
        // Select only id column to prevent wide row reads
        $log = EmailLog::where('tracking_token', $token)->select('id')->first();
        
        if ($log) {
            ProcessTrackingEventJob::dispatch($log->id, 'open', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ]);
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
        $log = EmailLog::where('tracking_token', $token)->select('id')->first();

        if ($log) {
            ProcessTrackingEventJob::dispatch($log->id, 'click', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
                'url' => $url
            ]);
        }

        return redirect()->away($url ?: '/');
    }

    public function unsubscribe(Request $request, string $token)
    {
        $log = EmailLog::where('tracking_token', $token)->with(['email', 'campaign'])->first();

        if (!$log || !$log->email) {
            return abort(404);
        }

        $email = $log->email;
        $campaign = $log->campaign;

        if ($request->isMethod('post')) {
            // One-Click List-Unsubscribe (POST)
            if ($campaign && $campaign->subscription_topic_id) {
                $topicId = $campaign->subscription_topic_id;
                
                // If subscribed_topics is null, they are subscribed to all topics.
                // We resolve all topics for the workspace and exclude this campaign's topic.
                if (is_null($email->subscribed_topics)) {
                    $allTopics = \App\Models\SubscriptionTopic::where('email_list_id', $email->email_list_id)
                        ->pluck('id')
                        ->toArray();
                    $newSubscribedTopics = array_values(array_diff($allTopics, [$topicId]));
                } else {
                    $newSubscribedTopics = array_values(array_diff($email->subscribed_topics, [$topicId]));
                }

                $email->update([
                    'subscribed_topics' => $newSubscribedTopics,
                ]);

                // Log Activity Feed
                \App\Models\ContactActivity::create([
                    'email_id' => $email->id,
                    'campaign_id' => $campaign->id,
                    'type' => 'preferences_updated',
                ]);
            } else {
                // Global Unsubscribe
                $email->update([
                    'subscription_status' => 'unsubscribed',
                    'unsubscribed_at' => now(),
                    'subscribed_topics' => [],
                ]);

                // Campaign Specific Analytics
                \App\Models\Unsubscribe::create([
                    'email' => $email->email,
                    'campaign_id' => $campaign ? $campaign->id : null,
                    'unsubscribed_at' => now(),
                ]);

                // Activity Feed
                \App\Models\ContactActivity::create([
                    'email_id' => $email->id,
                    'campaign_id' => $campaign ? $campaign->id : null,
                    'type' => 'unsubscribed',
                ]);
            }

            // Trigger segment recalculation for this contact

            return response()->json(['success' => true]);
        }

        // GET: Redirect to confirm/preferences page
        $secureToken = hash_hmac('sha256', $email->id . $email->email, config('app.key'));

        return redirect()->route('unsubscribe.show', [
            'id' => $email->id,
            'token' => $secureToken,
            'lid' => $log->id
        ]);
    }
}
