<?php

namespace App\Http\Controllers;

use App\Models\GlobalSetting;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    public function index()
    {
        $pricing = GlobalSetting::get('pricing_rules');
        return view('billing.plans', compact('pricing'));
    }

    public function purchase(Request $request, \App\Services\CashfreeService $cashfree)
    {
        $request->validate([
            'contacts' => 'required|integer|min:1000',
            'emails'   => 'required|integer|min:1000',
            'amount'   => 'required|numeric',
        ]);

        $amount = round($request->amount, 2);

        $user = auth()->user();
        $orderId = 'ORDER_' . strtoupper(\Illuminate\Support\Str::random(10));

        // Create Pending Invoice
        $invoice = \App\Models\Invoice::create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-' . time(),
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_id' => $orderId,
            'plan_details' => [
                'contacts_limit' => $request->contacts,
                'emails_limit' => $request->emails,
            ]
        ]);

        // Create Cashfree Order
        $order = $cashfree->createOrder($orderId, $amount, [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ], [
            'invoice_id' => (string) $invoice->id,
            'contacts' => (string) $request->contacts,
            'emails' => (string) $request->emails,
        ]);

        if (isset($order['payment_session_id'])) {
            \Illuminate\Support\Facades\Log::info('Cashfree Order Created', ['response' => $order]);
            return view('billing.checkout', ['sessionId' => $order['payment_session_id']]);
        }

        \Illuminate\Support\Facades\Log::error('Cashfree Payment Failed', ['response' => $order]);
        return back()->with('error', 'Payment initiation failed. Please check Cashfree configuration.');
    }
}
