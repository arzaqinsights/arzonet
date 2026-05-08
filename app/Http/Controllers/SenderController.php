<?php

namespace App\Http\Controllers;

use App\Models\Sender;
use App\Services\SESService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SenderController extends Controller
{
    protected SESService $sesService;
    protected \App\Services\MailService $mailService;

    public function __construct(SESService $sesService, \App\Services\MailService $mailService)
    {
        $this->sesService = $sesService;
        $this->mailService = $mailService;
    }

    public function index()
    {
        $query = Sender::orderBy('created_at', 'desc');
        
        // If not admin, show only their own senders
        if (Auth::check() && !Auth::user()->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        // Auto-check pending SES senders
        $pendingSenders = (clone $query)->where('type', 'ses')->where('status', 'pending')->get();
        foreach ($pendingSenders as $sender) {
            $awsStatus = $this->sesService->checkVerificationStatus($sender->email);
            if ($awsStatus === 'Success') {
                $sender->update(['status' => 'verified', 'verified_at' => now()]);
            } elseif ($awsStatus === 'Failed') {
                $sender->update(['status' => 'failed']);
            }
        }

        $senders = $query->paginate(15);
        return view('senders.index', compact('senders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:ses,smtp,sendgrid',
            'from_name' => 'required|string|max:255',
            'email' => 'required|email|unique:senders,email',
            'emails_per_second' => 'required|integer|min:1',
            'emails_per_minute' => 'required|integer|min:1',
            'daily_limit' => 'required|integer|min:1',
        ]);

        $data = [
            'user_id' => Auth::id(),
            'email' => strtolower($request->email),
            'from_name' => $request->from_name,
            'type' => $request->type,
            'emails_per_second' => $request->emails_per_second,
            'emails_per_minute' => $request->emails_per_minute,
            'daily_limit' => $request->daily_limit,
        ];

        if ($request->type === 'ses') {
            $request->validate([
                'ses_key' => 'required|string',
                'ses_secret' => 'required|string',
                'ses_region' => 'required|string',
            ]);

            $data = array_merge($data, [
                'status' => 'pending',
                'ses_key' => $request->ses_key,
                'ses_secret' => $request->ses_secret,
                'ses_region' => $request->ses_region,
            ]);
            
            $sender = Sender::create($data);
            $this->sesService->sendVerificationEmail($data['email']);
            return redirect()->back()->with('success', 'SES Sender added and verification email sent!');

        } elseif ($request->type === 'sendgrid') {
            $request->validate([
                'sendgrid_api_key' => 'required|string',
            ]);

            $data = array_merge($data, [
                'status' => 'verified', // SendGrid is usually pre-verified via API Key
                'sendgrid_api_key' => $request->sendgrid_api_key,
                'verified_at' => now(),
            ]);

            Sender::create($data);
            return redirect()->back()->with('success', 'SendGrid Sender added successfully!');

        } else {
            // SMTP
            $request->validate([
                'smtp_host' => 'required|string',
                'smtp_port' => 'required|integer',
                'smtp_username' => 'required|string',
                'smtp_password' => 'required|string',
                'smtp_encryption' => 'nullable|string|in:tls,ssl,none',
            ]);

            $data = array_merge($data, [
                'status' => 'verified',
                'smtp_host' => $request->smtp_host,
                'smtp_port' => $request->smtp_port,
                'smtp_username' => $request->smtp_username,
                'smtp_password' => $request->smtp_password,
                'smtp_encryption' => $request->smtp_encryption ?? 'tls',
                'verified_at' => now(),
            ]);

            Sender::create($data);
            return redirect()->back()->with('success', 'SMTP Sender added successfully!');
        }
    }

    public function retry(Sender $sender)
    {
        if ($sender->type !== 'ses' || $sender->status === 'verified') {
            return redirect()->back()->with('error', 'Retry not available for this sender.');
        }

        $awsStatus = $this->sesService->checkVerificationStatus($sender->email);

        if ($awsStatus === 'Success') {
            $sender->update(['status' => 'verified', 'verified_at' => now()]);
            return redirect()->back()->with('success', 'Status updated from AWS.');
        }

        $success = $this->sesService->sendVerificationEmail($sender->email);

        if ($success) {
            $sender->update(['status' => 'pending']);
            return redirect()->back()->with('success', 'Verification email resent.');
        }

        return redirect()->back()->with('error', 'Failed to resend verification email.');
    }

    public function destroy(Sender $sender)
    {
        // Check ownership if not admin
        if (Auth::check() && !Auth::user()->isAdmin() && $sender->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $sender->delete();
        return redirect()->back()->with('success', 'Sender removed successfully.');
    }

    public function edit(Sender $sender)
    {
        // Check ownership if not admin
        if (Auth::check() && !Auth::user()->isAdmin() && $sender->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        return view('senders.edit', compact('sender'));
    }

    public function update(Request $request, Sender $sender)
    {
        // Check ownership if not admin
        if (Auth::check() && !Auth::user()->isAdmin() && $sender->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'from_name' => 'required|string|max:255',
            'emails_per_second' => 'required|integer|min:1',
            'emails_per_minute' => 'required|integer|min:1',
            'daily_limit' => 'required|integer|min:1',
        ]);

        $data = $request->only([
            'from_name', 'emails_per_second', 'emails_per_minute', 'daily_limit',
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
            'ses_key', 'ses_secret', 'ses_region', 'sendgrid_api_key'
        ]);

        $sender->update($data);

        return redirect()->route('admin.senders.index')->with('success', 'Sender updated successfully!');
    }

    public function testConnection(Sender $sender)
    {
        try {
            $this->mailService->send(
                sender: $sender,
                to: $sender->email,
                subject: "⚡ Connection Test Successful",
                html: "<h2>Connection Test Successful!</h2><p>Your SMTP/SES configuration for <b>{$sender->email}</b> is working perfectly.</p>"
            );

            return redirect()->back()->with('success', 'Test connection successful! Check your inbox.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }
}
