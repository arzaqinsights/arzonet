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
            'email'  => 'required|email|unique:blacklisted_emails,email',
            'reason' => 'nullable|string|max:255',
        ]);

        BlacklistedEmail::create([
            'email'  => strtolower($request->email),
            'reason' => $request->reason,
        ]);

        return back()->with('success', 'Email added to blacklist.');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'emails' => 'required|string',
        ]);

        $emails = array_filter(
            array_map('trim', explode("\n", $request->emails))
        );

        $added = 0;
        foreach ($emails as $email) {
            $email = strtolower($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                BlacklistedEmail::firstOrCreate(
                    ['email' => $email],
                    ['reason' => $request->reason ?? 'Bulk blacklist']
                );
                $added++;
            }
        }

        return back()->with('success', "{$added} emails added to blacklist.");
    }

    public function destroy(BlacklistedEmail $blacklistedEmail)
    {
        $blacklistedEmail->delete();
        return back()->with('success', 'Email removed from blacklist.');
    }
}
