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
        // 1. Try finding in SignupForm
        $form = \App\Models\SignupForm::with('emailList')->where('token', $token)->first();
        
        if ($form) {
            $list = $form->emailList;
            $topics = SubscriptionTopic::where('email_list_id', $list->id)->get();
            
            // Map custom field keys to names
            $customFieldLabels = [];
            $mapping = $list->column_mapping ?? [];
            foreach ($mapping as $header => $key) {
                if (is_array($key) || is_object($key)) {
                    continue;
                }
                if (is_string($key) && str_starts_with($key, 'custom_')) {
                    $customFieldLabels[$key] = $header;
                } else if (is_string($header) && str_starts_with($header, 'custom_')) {
                    $customFieldLabels[$header] = $key;
                }
            }
            
            return view('public.signup', compact('list', 'topics', 'form', 'customFieldLabels'));
        }

        // 2. Fallback to legacy EmailList signup_form_token
        $list = EmailList::where('signup_form_token', $token)->firstOrFail();
        $topics = SubscriptionTopic::where('email_list_id', $list->id)->get();
        $form = null; // No custom form config
        $customFieldLabels = [];
        
        return view('public.signup', compact('list', 'topics', 'form', 'customFieldLabels'));
    }

    public function submit(Request $request, $token)
    {
        // 1. Check if it's a custom form
        $form = \App\Models\SignupForm::with('emailList')->where('token', $token)->first();
        
        if ($form) {
            $list = $form->emailList;
        } else {
            // 2. Legacy fallback
            $list = EmailList::where('signup_form_token', $token)->firstOrFail();
        }

        // Build validation rules dynamically
        $rules = [
            'email' => 'required|email|max:255',
        ];

        // If it's a custom form, validate only the selected/active fields on it
        if ($form) {
            $customFieldsOnForm = $form->custom_fields ?? [];
            if (in_array('name', $customFieldsOnForm)) {
                $rules['name'] = 'required|string|max:255';
            }
            if (in_array('whatsapp_number', $customFieldsOnForm)) {
                $rules['whatsapp_number'] = 'nullable|string|max:30';
            }
            // For custom list attributes (e.g. custom_1, custom_2...)
            foreach ($customFieldsOnForm as $fieldKey) {
                if (str_starts_with($fieldKey, 'custom_')) {
                    $rules[$fieldKey] = 'nullable|string|max:255';
                }
            }
        } else {
            // Legacy form expects name and email by default
            $rules['name'] = 'required|string|max:255';
        }

        $request->validate($rules);

        // Find or instantiate contact
        $contact = Email::firstOrNew([
            'email_list_id' => $list->id,
            'email'         => $request->email,
        ]);

        $contact->user_id = $list->user_id;
        $contact->signup_source = 'public_form';
        $contact->status = 'valid'; // Signup is inherently a valid email

        if ($request->has('name')) {
            $contact->name = $request->name;
        }
        if ($request->has('whatsapp_number')) {
            $contact->whatsapp_number = $request->whatsapp_number;
        }

        // Map other custom columns to meta
        if ($form) {
            $meta = $contact->meta ?? [];
            foreach ($form->custom_fields ?? [] as $fieldKey) {
                if (str_starts_with($fieldKey, 'custom_')) {
                    $meta[$fieldKey] = $request->input($fieldKey);
                }
            }
            $contact->meta = $meta;
        }

        // Parse subscribed topics
        if ($form) {
            // Auto enroll into topics specified on form config
            $contact->subscribed_topics = array_map('strval', $form->subscribed_topics ?? []);
        } else {
            // Legacy default: subscribe to all list topics
            $contact->subscribed_topics = SubscriptionTopic::where('email_list_id', $list->id)
                ->pluck('id')
                ->map('strval')
                ->toArray();
        }

        $doubleOptIn = $form ? $form->double_opt_in : $list->double_opt_in;
        $successMessage = $form ? $form->success_message : 'Thank you! You have been successfully subscribed to our list.';
        $themeColor = $form ? $form->theme_color : '#5850ec';

        if ($doubleOptIn) {
            $contact->subscription_status = 'pending';
            $contact->save();

            // Send verification email
            $confirmUrl = route('public.confirm-subscription', ['token' => Crypt::encryptString($contact->id)]);
            $subject = "Confirm your subscription to " . $list->name;
            $html = "<h3>Please confirm your subscription</h3>"
                  . "<p>Thanks for subscribing to our list <strong>{$list->name}</strong>. Please click the link below to confirm your subscription:</p>"
                  . "<p style='margin: 20px 0;'><a href='{$confirmUrl}' style='display:inline-block;padding:12px 24px;background-color:{$themeColor};color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;'>Confirm Subscription</a></p>"
                  . "<p>If the button doesn't work, copy and paste this link in your browser:</p>"
                  . "<p><a href='{$confirmUrl}'>{$confirmUrl}</a></p>";

            $sender = Sender::where('user_id', $list->user_id)->first();
            if ($sender) {
                try {
                    $mailService = app(\App\Services\MailService::class);
                    $mailService->send($sender, $contact->email, $subject, $html, $contact);
                } catch (\Exception $e) {
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

        return back()->with('success', $successMessage);
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
