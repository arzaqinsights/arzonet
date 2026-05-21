<?php

namespace App\Http\Controllers;

use App\Models\GlobalSetting;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    public function pricingPage()
    {
        $pricing = GlobalSetting::get('pricing_rules') ?: [];
        return view('landing.pricing', compact('pricing'));
    }

    public function index(Request $request)
    {
        $pricing = GlobalSetting::get('pricing_rules') ?: [];

        // Handle checkout flow from the pricing page configurator
        if ($request->has('plan') && $request->query('plan') !== 'custom') {
            // Fixed plan selected — pull limits from config
            $planKey = $request->query('plan');
            $plan = config("plans.plans.{$planKey}");

            if (!$plan) {
                return redirect()->route('pricing')->with('error', 'Invalid plan selected.');
            }

            $details = $this->recalculatePricing(
                $planKey,
                $plan['limits']['crm_users'],
                $plan['limits']['crm_contacts'],
                $plan['limits']['emails_per_month'],
                $plan['limits']['whatsapp_numbers'],
                $plan['limits']['whatsapp_messages']
            );

            return view('billing.plans', [
                'pricing'    => $pricing,
                'checkout'   => true,
                'planKey'    => $planKey,
                'planName'   => $plan['name'],
                'limits'     => $plan['limits'],
                'details'    => $details,
            ]);
        }

        // Custom plan — user-picked quantities
        if ($request->query('plan') === 'custom') {
            $limits = [
                'crm_users'         => (int) $request->query('crm_users', 5),
                'crm_contacts'      => (int) $request->query('crm_contacts', 10000),
                'emails_per_month'  => (int) $request->query('emails_per_month', 25000),
                'whatsapp_numbers'  => (int) $request->query('whatsapp_numbers', 2),
                'whatsapp_messages' => (int) $request->query('whatsapp_messages', 5000),
            ];

            $details = $this->recalculatePricing(
                'custom',
                $limits['crm_users'],
                $limits['crm_contacts'],
                $limits['emails_per_month'],
                $limits['whatsapp_numbers'],
                $limits['whatsapp_messages']
            );

            return view('billing.plans', [
                'pricing'    => $pricing,
                'checkout'   => true,
                'planKey'    => 'custom',
                'planName'   => 'Custom',
                'limits'     => $limits,
                'details'    => $details,
            ]);
        }

        $user = auth()->user();
        $subscription = $user->subscription;

        // Active metrics count
        $contactsCount = $user->emails()->count();
        $emailsCount = $user->logs()->whereIn('status', ['sent', 'delivered'])->count();
        $whatsappCount = $user->whatsappAccounts()->count();
        $teamCount = \App\Models\User::where('role', 'team')->count();

        return view('billing.plans', [
            'pricing'        => $pricing,
            'checkout'       => false,
            'subscription'   => $subscription,
            'contactsCount'  => $contactsCount,
            'emailsCount'    => $emailsCount,
            'whatsappCount'  => $whatsappCount,
            'teamCount'      => $teamCount,
        ]);
    }

    /**
     * Calculate pricing for a plan selection.
     * For fixed plans: uses the fixed plan price.
     * For custom: sums per-unit rates for user-chosen quantities.
     */
    public function recalculatePricing($planKey, $crmUsers, $crmContacts, $emailsPerMonth, $whatsappNumbers, $whatsappMessages)
    {
        $pricingRules = GlobalSetting::get('pricing_rules') ?: [];
        $taxPercent = (float) ($pricingRules['tax_percent'] ?? 18);
        $rates = config('plans.rates');

        if ($planKey !== 'custom') {
            // Fixed plan — use plan price directly
            $plan = config("plans.plans.{$planKey}");
            $basePrice = $plan['price'] ?? 0;
            $extraPrice = 0;
        } else {
            // Custom plan — calculate from per-unit rates
            $basePrice = 0;
            $extraPrice = 0;

            // CRM users: ₹600 per user
            $basePrice += $crmUsers * $rates['crm_per_user'];

            // CRM contacts: ₹20 per 1,000 contacts
            $basePrice += ($crmContacts / 1000) * $rates['crm_per_1k_contacts'];

            // Emails: ₹100 per 1,000 emails
            $basePrice += ($emailsPerMonth / 1000) * $rates['email_per_1k'];

            // WhatsApp numbers: ₹500 per number
            $basePrice += $whatsappNumbers * $rates['whatsapp_per_number'];

            // WhatsApp messages: ₹0.90 per message
            $basePrice += $whatsappMessages * $rates['whatsapp_per_message'];

            $basePrice = round($basePrice);
        }

        $subtotal = $basePrice + $extraPrice;
        $taxAmount = ($subtotal * $taxPercent) / 100;
        $grandTotal = round($subtotal + $taxAmount, 2);

        return [
            'base_price'  => $basePrice,
            'extra_price' => $extraPrice,
            'subtotal'    => $subtotal,
            'tax_amount'  => $taxAmount,
            'grand_total' => $grandTotal,
        ];
    }

    public function purchase(Request $request, \App\Services\CashfreeService $cashfree)
    {
        $request->validate([
            'plan'               => 'required|string|in:starter,growth,business,custom',
            'crm_users'          => 'nullable|integer|min:1',
            'crm_contacts'       => 'nullable|integer|min:1000',
            'emails_per_month'   => 'nullable|integer|min:5000',
            'whatsapp_numbers'   => 'nullable|integer|min:1',
            'whatsapp_messages'  => 'nullable|integer|min:1000',
        ]);

        $planKey = $request->plan;

        if ($planKey !== 'custom') {
            $plan = config("plans.plans.{$planKey}");
            $limits = $plan['limits'];
        } else {
            $limits = [
                'crm_users'         => (int) ($request->crm_users ?? 5),
                'crm_contacts'      => (int) ($request->crm_contacts ?? 10000),
                'emails_per_month'  => (int) ($request->emails_per_month ?? 25000),
                'whatsapp_numbers'  => (int) ($request->whatsapp_numbers ?? 2),
                'whatsapp_messages' => (int) ($request->whatsapp_messages ?? 5000),
            ];
        }

        // Recalculate and verify pricing on the backend
        $pricingDetails = $this->recalculatePricing(
            $planKey,
            $limits['crm_users'],
            $limits['crm_contacts'],
            $limits['emails_per_month'],
            $limits['whatsapp_numbers'],
            $limits['whatsapp_messages']
        );
        $amount = $pricingDetails['grand_total'];

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
                'plan'   => $planKey,
                'limits' => $limits,
            ]
        ]);

        // Create Cashfree Order
        $order = $cashfree->createOrder($orderId, $amount, [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ], [
            'invoice_id' => (string) $invoice->id,
            'plan'        => $planKey,
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
            $planKey = $details['plan'] ?? 'starter';
            $limits = $details['limits'] ?? [];

            \App\Models\Subscription::updateOrCreate(
                ['user_id' => $invoice->user_id],
                [
                    'plan_name'        => ucfirst($planKey) . ' Plan',
                    'contacts_limit'   => $limits['crm_contacts'] ?? 0,
                    'emails_limit'     => $limits['emails_per_month'] ?? 0,
                    'selected_modules' => ['crm', 'email', 'whatsapp'], // All modules always included
                    'whatsapp_limit'   => $limits['whatsapp_numbers'] ?? 0,
                    'team_limit'       => $limits['crm_users'] ?? 0,
                    'status'           => 'active',
                    'starts_at'        => now(),
                    'ends_at'          => now()->addMonth(),
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
