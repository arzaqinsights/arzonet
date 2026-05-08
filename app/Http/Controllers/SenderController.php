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
        $senders = Sender::where('user_id', Auth::id())->latest()->get();
        return view('senders.index', compact('senders'));
    }

    public function create()
    {
        return view('senders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_name' => 'required|string|max:255',
            'email' => 'required|email|unique:senders,email',
            'mode' => 'required|in:bulk,normal',
            // SMTP still needs credentials if normal
            'smtp_host' => 'required_if:mode,normal',
            'smtp_port' => 'required_if:mode,normal',
            'smtp_username' => 'required_if:mode,normal',
            'smtp_password' => 'required_if:mode,normal',
        ]);

        $mode = $request->mode;
        $profile = config("emailplatform.profiles.{$mode}");
        $type = $mode === 'bulk' ? config('emailplatform.bulk_provider') : 'smtp';

        $sender = Sender::create([
            'user_id' => Auth::id(),
            'from_name' => $request->from_name,
            'email' => $request->email,
            'type' => $type,
            'emails_per_second' => $profile['emails_per_second'],
            'emails_per_minute' => $profile['emails_per_minute'],
            'daily_limit' => $profile['daily_limit'],
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'smtp_username' => $request->smtp_username,
            'smtp_password' => $request->smtp_password,
            'status' => $mode === 'bulk' ? 'pending' : 'verified',
            'verified_at' => $mode === 'bulk' ? null : now(),
        ]);

        if ($type === 'ses') {
            try {
                $ses = new SESService();
                $ses->verifyEmail($sender->email);
            } catch (\Exception $e) {}
        }

        return redirect()->route('admin.senders.index')->with('success', 'Sender configured successfully in ' . ucfirst($mode) . ' mode.');
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
                    $sender->update([
                        'status' => 'verified',
                        'verified_at' => now()
                    ]);
                    return back()->with('success', 'Sender verified successfully.');
                }
            } catch (\Exception $e) {
                return back()->with('error', 'Verification check failed: ' . $e->getMessage());
            }
        }

        return back()->with('info', 'Verification is still pending.');
    }

    public function destroy(Sender $sender)
    {
        $sender->delete();
        return back()->with('success', 'Sender deleted.');
    }
}
