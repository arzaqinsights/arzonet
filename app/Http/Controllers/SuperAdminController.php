<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GlobalSetting;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_contacts' => \App\Models\Email::count(),
            'total_emails_sent' => \App\Models\EmailLog::whereIn('status', ['sent', 'delivered'])->count(),
            'active_subscriptions' => \App\Models\Subscription::where('status', 'active')->count(),
        ];

        $recentInvoices = \App\Models\Invoice::with('user')->latest()->take(10)->get();

        return view('super-admin.dashboard', compact('stats', 'recentInvoices'));
    }

    public function users()
    {
        $users = User::with(['subscription', 'emailLists'])
            ->withCount(['emails', 'logs as sent_emails_count' => function($q) {
                $q->whereIn('status', ['sent', 'delivered']);
            }])
            ->latest()
            ->paginate(20);

        return view('super-admin.users.index', compact('users'));
    }

    public function settings()
    {
        $pricing = GlobalSetting::get('pricing_rules');
        return view('super-admin.settings', compact('pricing'));
    }

    public function updateSettings(Request $request)
    {
        $rules = $request->validate([
            'contacts_base_price' => 'required|numeric',
            'emails_base_price'   => 'required|numeric',
            'discounts'           => 'required|array',
            'tax_percent'         => 'required|numeric',
        ]);

        GlobalSetting::set('pricing_rules', $rules);

        return back()->with('success', 'Pricing updated successfully!');
    }

    public function suspend(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Admin accounts cannot be suspended.');
        }

        $request->validate([
            'suspension_reason' => 'required|string|max:1000',
        ]);

        $user->update([
            'is_suspended' => true,
            'suspension_reason' => $request->suspension_reason,
        ]);

        return back()->with('success', 'User account suspended successfully.');
    }

    public function unsuspend(User $user)
    {
        $user->update([
            'is_suspended' => false,
            'suspension_reason' => null,
        ]);

        return back()->with('success', 'User account unsuspended successfully.');
    }
}
