<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class CustomPlanCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://email.test']);
        config(['app.domain' => 'email.test']);

        $this->user = User::factory()->create();
    }

    /**
     * Test checkout flow with custom plan having unselected modules (zero limits).
     */
    public function test_checkout_with_unselected_modules()
    {
        $this->actingAs($this->user);

        // Fake Cashfree Order API response
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response([
                'payment_session_id' => 'fake_session_123',
                'cf_order_id' => 'fake_cf_order_123',
                'order_status' => 'ACTIVE'
            ], 200)
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.billing.purchase', [], false);

        $response = $this->post($url, [
            'plan' => 'custom',
            'crm_users' => 5,
            'crm_contacts' => 10000,
            'emails_per_month' => 0,
            'whatsapp_numbers' => 0,
            'whatsapp_messages' => 0,
        ], [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('billing.checkout');
        $response->assertViewHas('sessionId', 'fake_session_123');

        // Verify pending invoice with 0 limits exists
        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals('pending', $invoice->status);
        $this->assertEquals(0, $invoice->plan_details['limits']['emails_per_month']);
        $this->assertEquals(0, $invoice->plan_details['limits']['whatsapp_numbers']);
    }

    /**
     * Test payment return activation of subscription with only CRM selected.
     */
    public function test_payment_return_activates_crm_only()
    {
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-12345',
            'amount' => 3658,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_id' => 'ORDER_ABC123',
            'plan_details' => [
                'plan' => 'custom',
                'limits' => [
                    'crm_users' => 5,
                    'crm_contacts' => 10000,
                    'emails_per_month' => 0,
                    'whatsapp_numbers' => 0,
                    'whatsapp_messages' => 0,
                ]
            ]
        ]);

        // Fake Cashfree retrieve order API response
        Http::fake([
            'sandbox.cashfree.com/*' => Http::response([
                'order_status' => 'PAID',
                'cf_order_id' => 'ORDER_ABC123'
            ], 200)
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.billing.payment-return', ['order_id' => 'ORDER_ABC123'], false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $response->assertRedirect(route('admin.billing.plans'));

        // Check active subscription
        $subscription = Subscription::first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals(0, $subscription->emails_limit);
        $this->assertEquals(0, $subscription->whatsapp_limit);
        $this->assertEquals(['crm'], $subscription->selected_modules);
    }

    /**
     * Test payment return activation of subscription with CRM + Email selected.
     */
    public function test_payment_return_activates_crm_and_email()
    {
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-12346',
            'amount' => 5000,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_id' => 'ORDER_DEF456',
            'plan_details' => [
                'plan' => 'custom',
                'limits' => [
                    'crm_users' => 5,
                    'crm_contacts' => 10000,
                    'emails_per_month' => 20000,
                    'whatsapp_numbers' => 0,
                    'whatsapp_messages' => 0,
                ]
            ]
        ]);

        Http::fake([
            'sandbox.cashfree.com/*' => Http::response([
                'order_status' => 'PAID',
                'cf_order_id' => 'ORDER_DEF456'
            ], 200)
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.billing.payment-return', ['order_id' => 'ORDER_DEF456'], false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $subscription = Subscription::first();
        $this->assertNotNull($subscription);
        $this->assertEquals(20000, $subscription->emails_limit);
        $this->assertEquals(0, $subscription->whatsapp_limit);
        $this->assertEquals(['crm', 'email'], $subscription->selected_modules);
    }

    /**
     * Test payment return activation of subscription with CRM + WhatsApp selected.
     */
    public function test_payment_return_activates_crm_and_whatsapp()
    {
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-12347',
            'amount' => 4500,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_id' => 'ORDER_GHI789',
            'plan_details' => [
                'plan' => 'custom',
                'limits' => [
                    'crm_users' => 5,
                    'crm_contacts' => 10000,
                    'emails_per_month' => 0,
                    'whatsapp_numbers' => 2,
                    'whatsapp_messages' => 5000,
                ]
            ]
        ]);

        Http::fake([
            'sandbox.cashfree.com/*' => Http::response([
                'order_status' => 'PAID',
                'cf_order_id' => 'ORDER_GHI789'
            ], 200)
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.billing.payment-return', ['order_id' => 'ORDER_GHI789'], false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $subscription = Subscription::first();
        $this->assertNotNull($subscription);
        $this->assertEquals(0, $subscription->emails_limit);
        $this->assertEquals(2, $subscription->whatsapp_limit);
        $this->assertEquals(['crm', 'whatsapp'], $subscription->selected_modules);
    }

    /**
     * Test payment return activation of subscription with all modules selected.
     */
    public function test_payment_return_activates_all_modules()
    {
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-12348',
            'amount' => 8000,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_id' => 'ORDER_JKL101',
            'plan_details' => [
                'plan' => 'custom',
                'limits' => [
                    'crm_users' => 5,
                    'crm_contacts' => 10000,
                    'emails_per_month' => 25000,
                    'whatsapp_numbers' => 2,
                    'whatsapp_messages' => 5000,
                ]
            ]
        ]);

        Http::fake([
            'sandbox.cashfree.com/*' => Http::response([
                'order_status' => 'PAID',
                'cf_order_id' => 'ORDER_JKL101'
            ], 200)
        ]);

        $url = 'http://admin.' . config('app.domain') . route('admin.billing.payment-return', ['order_id' => 'ORDER_JKL101'], false);

        $response = $this->get($url, [
            'Host' => 'admin.' . config('app.domain')
        ]);

        $subscription = Subscription::first();
        $this->assertNotNull($subscription);
        $this->assertEquals(25000, $subscription->emails_limit);
        $this->assertEquals(2, $subscription->whatsapp_limit);
        $this->assertEquals(['crm', 'email', 'whatsapp'], $subscription->selected_modules);
    }
}
