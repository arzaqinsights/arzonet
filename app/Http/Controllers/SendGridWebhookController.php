<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\Campaign;
use App\Models\Unsubscribe;
use App\Models\EmailEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SendGridWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $events = $request->getContent();
        if (empty($events)) {
            return response()->json(['status' => 'empty'], 200);
        }
        
        // Push raw payload into Redis buffer. Zero DB work.
        \Illuminate\Support\Facades\Redis::rpush('webhook:sendgrid:buffer', $events);
        
        return response()->json(['status' => 'buffered'], 200);
    }
}

