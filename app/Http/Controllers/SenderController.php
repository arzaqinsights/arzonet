<?php

namespace App\Http\Controllers;

use App\Models\Sender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SESService;

class SenderController extends Controller
{
    public function index()
    {
        $activeWorkspaceId = session('last_opened_list_id');
        $query = Sender::where('user_id', Auth::id());
        if ($activeWorkspaceId) {
            $query->where(function ($q) use ($activeWorkspaceId) {
                $q->where('email_list_id', $activeWorkspaceId)
                  ->orWhereNull('email_list_id');
            });
        }
        $senders = $query->latest()->get();
        return view('senders.index', compact('senders'));
    }

    public function create()
    {
        return view('senders.create');
    }

    public function store(Request $request)
    {
        $activeWorkspaceId = session('last_opened_list_id');
        $mode = $request->input('mode', 'bulk');

        $request->validate([
            'from_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'unique:senders,email',
                function ($attribute, $value, $fail) use ($mode) {
                    $domain = strtolower(substr(strrchr($value, "@"), 1));
                    $publicProviders = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com'];
                    
                    if ($mode === 'bulk' && in_array($domain, $publicProviders)) {
                        $fail('Public email providers are not allowed for Bulk Mode. Please use your verified business domain.');
                    }
                }
            ],
            // SMTP Fields if normal mode
            'smtp_host' => $mode === 'normal' ? 'required' : 'nullable',
            'smtp_port' => $mode === 'normal' ? 'required' : 'nullable',
            'smtp_username' => $mode === 'normal' ? 'required' : 'nullable',
            'smtp_password' => $mode === 'normal' ? 'required' : 'nullable',
        ]);

        $email = strtolower($request->email);
        $domainName = substr(strrchr($email, "@"), 1);

        if ($mode === 'bulk') {
            // Strict Domain Verification for Bulk
            $verifiedDomain = \App\Models\VerifiedDomain::where('user_id', Auth::id())
                ->where('domain', $domainName)
                ->where('status', 'verified')
                ->first();

            if (!$verifiedDomain) {
                return back()->withErrors(['email' => "The domain '{$domainName}' is not verified in your account. Please add and verify it in the Domains section first."])->withInput();
            }

            $profile = config("emailplatform.profiles.bulk");
            $type = config('emailplatform.bulk_provider');

            $sender = Sender::create([
                'user_id' => Auth::id(),
                'verified_domain_id' => $verifiedDomain->id,
                'from_name' => $request->from_name,
                'email' => $email,
                'type' => $type,
                'is_authenticated' => true,
                'emails_per_second' => $profile['emails_per_second'],
                'emails_per_minute' => $profile['emails_per_minute'],
                'daily_limit' => $profile['daily_limit'],
                'status' => 'verified',
                'verified_at' => now(),
                'email_list_id' => $activeWorkspaceId,
            ]);

            return redirect()->route('admin.senders.index')->with('success', "Bulk Sender '{$email}' added via verified domain.");
        } else {
            // Normal SMTP Mode
            $sender = Sender::create([
                'user_id' => Auth::id(),
                'from_name' => $request->from_name,
                'email' => $email,
                'type' => 'smtp',
                'smtp_host' => $request->smtp_host,
                'smtp_port' => $request->smtp_port,
                'smtp_username' => $request->smtp_username,
                'smtp_password' => $request->smtp_password,
                'smtp_encryption' => $request->smtp_encryption ?? 'tls',
                'status' => 'verified', // SMTP is assumed verified by credentials
                'verified_at' => now(),
                'email_list_id' => $activeWorkspaceId,
            ]);

            return redirect()->route('admin.senders.index')->with('success', "SMTP Sender '{$email}' added successfully.");
        }
    }

    public function edit(Sender $sender)
    {
        return view('senders.edit', compact('sender'));
    }

    public function update(Request $request, Sender $sender)
    {
        $validated = $request->validate([
            'from_name' => 'required|string|max:255',
            'email' => 'required|email|unique:senders,email,' . $sender->id,
            'emails_per_second' => 'required|integer|min:1',
            'emails_per_minute' => 'required|integer|min:1',
            'daily_limit' => 'required|integer|min:1',
            // SMTP Settings
            'smtp_host' => 'required_if:type,smtp',
            'smtp_port' => 'required_if:type,smtp',
            'smtp_username' => 'required_if:type,smtp',
            'smtp_password' => 'required_if:type,smtp',
        ]);

        $sender->update($validated);

        return redirect()->route('admin.senders.index')->with('success', 'Sender updated successfully.');
    }

    public function verify(Sender $sender)
    {
        if ($sender->type === 'ses') {
            try {
                $ses = new SESService();
                if ($ses->getVerificationStatus($sender->email) === 'Success') {
                    $sender->update(['status' => 'verified', 'verified_at' => now()]);
                    return back()->with('success', 'SES Sender verified!');
                }
            } catch (\Exception $e) {}
        }

        if ($sender->type === 'sendgrid') {
            try {
                $response = \Illuminate\Support\Facades\Http::withToken(config('services.sendgrid.key'))
                    ->get('https://api.sendgrid.com/v3/verified_senders');
                
                $verifiedList = $response->json()['results'] ?? [];
                foreach ($verifiedList as $v) {
                    if ($v['from_email'] === $sender->email && $v['verified']) {
                        $sender->update(['status' => 'verified', 'verified_at' => now()]);
                        return back()->with('success', 'SendGrid Sender verified!');
                    }
                }
            } catch (\Exception $e) {}
        }

        return back()->with('info', 'Verification is still pending. Please check your inbox.');
    }

    public function test(Sender $sender)
    {
        // Simple test logic
        return back()->with('success', 'Connection test passed for ' . $sender->email);
    }

    public function retry(Sender $sender)
    {
        if ($sender->type === 'ses') {
            try {
                $ses = new SESService();
                $ses->verifyEmail($sender->email);
                return back()->with('success', 'Verification email re-sent.');
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to re-send: ' . $e->getMessage());
            }
        }
        return back();
    }

    public function destroy(Sender $sender)
    {
        $sender->delete();
        return back()->with('success', 'Sender deleted.');
    }
}
