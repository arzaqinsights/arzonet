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

    /**
     * Handle user return from Cashfree payment gateway.
     * Verify order status and activate plan if paid.
     */
    public function paymentReturn(Request $request, \App\Services\CashfreeService $cashfree)
    {
        $orderId = $request->query('order_id');

        if (!$orderId) {
            return redirect()->route('admin.billing.plans')->with('error', 'Invalid payment return. No order ID found.');
        }

        // Verify with Cashfree API
        $order = $cashfree->getOrder($orderId);
        $status = $order['order_status'] ?? 'UNKNOWN';

        $invoice = \App\Models\Invoice::where('payment_id', $orderId)->first();

        if (!$invoice) {
            return redirect()->route('admin.billing.plans')->with('error', 'Order not found in our records.');
        }

        if ($status === 'PAID' && $invoice->status !== 'paid') {
            $invoice->update([
                'status' => 'paid',
                'payment_id' => $order['cf_order_id'] ?? $orderId,
            ]);

            // Activate subscription
            $details = $invoice->plan_details;
            \App\Models\Subscription::updateOrCreate(
                ['user_id' => $invoice->user_id],
                [
                    'contacts_limit' => $details['contacts_limit'],
                    'emails_limit' => $details['emails_limit'],
                    'plan_name' => 'Custom Power Plan',
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                ]
            );

            // Send Invoice Email
            try {
                \Illuminate\Support\Facades\Mail::to($invoice->user->email)->send(new \App\Mail\InvoiceMail($invoice));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send invoice email: " . $e->getMessage());
            }

            return redirect()->route('admin.billing.plans')->with('success', 'Payment successful! Your plan has been activated.');
        }

        if ($status === 'PAID') {
            return redirect()->route('admin.billing.plans')->with('success', 'Your plan is already active.');
        }

        return redirect()->route('admin.billing.plans')->with('error', 'Payment was not completed. Status: ' . $status);
    }
}
