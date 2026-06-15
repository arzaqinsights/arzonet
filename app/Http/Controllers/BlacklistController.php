<?php

namespace App\Http\Controllers;

use App\Models\BlacklistedEmail;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    public function index()
    {
        $blacklist = BlacklistedEmail::latest()->paginate(50);
        return view('blacklist.index', compact('blacklist'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'  => 'required|email',
            'reason' => 'nullable|string|max:255',
        ]);

        $email = strtolower(trim($request->email));

        // Scoped to the current owner automatically by BelongsToUser trait
        $exists = BlacklistedEmail::where('email', $email)->exists();
        if ($exists) {
            return back()->with('error', 'This email is already on your blacklist.');
        }

        BlacklistedEmail::create([
            'email'  => $email,
            'reason' => $request->reason,
        ]);

        // Mark matching contacts across all lists/workspaces of this user as blacklisted
        \App\Models\Email::where('email', $email)
            ->update([
                'status' => 'invalid',
                'email_status' => 'blocked',
                'subscription_status' => 'unsubscribed',
                'validation_reason' => 'Blacklisted email',
            ]);

        return back()->with('success', 'Email added to blacklist.');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'emails' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        // Split by commas, newlines, semicolons, or whitespace
        $rawEmails = preg_split('/[\s,;]+/', $request->emails);
        
        $added = 0;
        $emailsToBlock = [];

        foreach ($rawEmails as $rawEmail) {
            $email = strtolower(trim($rawEmail));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Scoped automatically by BelongsToUser
                $exists = BlacklistedEmail::where('email', $email)->exists();
                if (!$exists) {
                    BlacklistedEmail::create([
                        'email' => $email,
                        'reason' => $request->reason ?? 'Bulk blacklist'
                    ]);
                    $emailsToBlock[] = $email;
                    $added++;
                }
            }
        }

        if (!empty($emailsToBlock)) {
            // Bulk update existing contacts for this user across all lists/workspaces
            \App\Models\Email::whereIn('email', $emailsToBlock)
                ->update([
                    'status' => 'invalid',
                    'email_status' => 'blocked',
                    'subscription_status' => 'unsubscribed',
                    'validation_reason' => 'Blacklisted email',
                ]);
        }

        return back()->with('success', "{$added} emails added to blacklist and matching contacts updated across all your lists.");
    }

    public function destroy(BlacklistedEmail $blacklistedEmail)
    {
        $email = $blacklistedEmail->email;
        $blacklistedEmail->delete();

        // Restore matching contacts status back to valid if they were blocked
        \App\Models\Email::where('email', $email)
            ->where('email_status', 'blocked')
            ->update([
                'status' => 'valid',
                'email_status' => 'valid',
                'validation_reason' => 'Removed from blacklist',
            ]);

        return back()->with('success', 'Email removed from blacklist.');
    }
}
