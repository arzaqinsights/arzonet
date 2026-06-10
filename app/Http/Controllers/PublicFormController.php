<?php

namespace App\Http\Controllers;

use App\Models\EmailList;
use App\Models\Email;
use App\Models\Sender;
use App\Models\PreferenceLog;
use App\Models\SubscriptionTopic;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class PublicFormController extends Controller
{
    public function show($token)
    {
        $list = EmailList::where('signup_form_token', $token)->firstOrFail();
        $topics = SubscriptionTopic::where('email_list_id', $list->id)->get();

        return view('public.signup', compact('list', 'topics'));
    }

    public function submit(Request $request, $token)
    {
        $list = EmailList::where('signup_form_token', $token)->firstOrFail();

        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|max:255',
            'topics' => 'nullable|array',
        ]);

        // Find or instantiate contact
        $contact = Email::firstOrNew([
            'email_list_id' => $list->id,
            'email'         => $request->email,
        ]);

        $contact->name = $request->name;
        $contact->user_id = $list->user_id;
        $contact->signup_source = 'public_form';
        $contact->status = 'valid'; // Signup is inherently a valid email

        // Parse subscribed topics
        $topicIds = $request->input('topics', []);
        if (empty($topicIds) && $request->has('has_topics_field')) {
            // User submitted form with empty topics checkbox, mean unsubscribe from all
            $contact->subscribed_topics = [];
        } elseif (empty($topicIds)) {
            // Default: subscribe to all list topics
            $contact->subscribed_topics = SubscriptionTopic::where('email_list_id', $list->id)
                ->pluck('id')
                ->map('strval')
                ->toArray();
        } else {
            $contact->subscribed_topics = array_map('strval', $topicIds);
        }

        if ($list->double_opt_in) {
            $contact->subscription_status = 'pending';
            $contact->save();

            // Send verification email
            $confirmUrl = route('public.confirm-subscription', ['token' => Crypt::encryptString($contact->id)]);
            $subject = "Confirm your subscription to " . $list->name;
            $html = "<h3>Please confirm your subscription</h3>"
                  . "<p>Thanks for subscribing to our list <strong>{$list->name}</strong>. Please click the link below to confirm your subscription:</p>"
                  . "<p style='margin: 20px 0;'><a href='{$confirmUrl}' style='display:inline-block;padding:12px 24px;background-color:#5850ec;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;'>Confirm Subscription</a></p>"
                  . "<p>If the button doesn't work, copy and paste this link in your browser:</p>"
                  . "<p><a href='{$confirmUrl}'>{$confirmUrl}</a></p>";

            $sender = Sender::where('user_id', $list->user_id)->first();
            if ($sender) {
                try {
                    $mailService = app(\App\Services\MailService::class);
                    $mailService->send($sender, $contact->email, $subject, $html, $contact);
                } catch (\Exception $e) {
                    // Failover to default mailer
                    Mail::html($html, function ($message) use ($contact, $subject) {
                        $message->to($contact->email)->subject($subject);
                    });
                }
            } else {
                Mail::html($html, function ($message) use ($contact, $subject) {
                    $message->to($contact->email)->subject($subject);
                });
            }

            return back()->with('success', 'Thank you for signing up! Please check your inbox to confirm your subscription.');
        }

        $contact->subscription_status = 'subscribed';
        $contact->save();

        // Log preference
        PreferenceLog::create([
            'email_id' => $contact->id,
            'action'   => 'subscribe',
            'details'  => [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'topics'     => $contact->subscribed_topics,
                'source'     => 'public_signup_form',
            ]
        ]);

        // Trigger visual workflow run
        Workflow::trigger('list_signup', $contact);

        return back()->with('success', 'Thank you! You have been successfully subscribed to our list.');
    }

    public function confirm($token)
    {
        try {
            $contactId = Crypt::decryptString($token);
            $contact = Email::findOrFail($contactId);
        } catch (\Exception $e) {
            abort(400, 'Invalid or expired confirmation link.');
        }

        if ($contact->subscription_status === 'pending') {
            $contact->subscription_status = 'subscribed';
            $contact->save();

            // Log preference
            PreferenceLog::create([
                'email_id' => $contact->id,
                'action'   => 'subscribe',
                'details'  => [
                    'ip'         => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'topics'     => $contact->subscribed_topics,
                    'source'     => 'double_opt_in_confirm',
                ]
            ]);

            // Trigger list signup journey run
            Workflow::trigger('list_signup', $contact);
        }

        return view('public.confirm', compact('contact'));
    }
}
