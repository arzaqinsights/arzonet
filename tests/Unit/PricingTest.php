<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\PlansController;
use Illuminate\Http\Request;

class PricingTest extends TestCase
{
    protected PlansController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PlansController();

        config([
            'plans.gst_percent' => 18,
            'plans.rates' => [
                'crm_per_user'             => 600,
                'crm_per_1k_contacts'      => 10,
                'email_per_1k'             => 100,
                'whatsapp_per_number'      => 500,
                'whatsapp_per_message'     => 0,
            ],
            'plans.plans' => [
                'starter' => [
                    'price' => 2200,
                    'limits' => [
                        'crm_users'          => 1,
                        'crm_contacts'       => 5000,
                        'emails_per_month'   => 10000,
                        'whatsapp_numbers'   => 1,
                        'whatsapp_messages'  => 1000,
                    ],
                ],
                'growth' => [
                    'price' => 10000,
                    'limits' => [
                        'crm_users'          => 5,
                        'crm_contacts'       => 25000,
                        'emails_per_month'   => 50000,
                        'whatsapp_numbers'   => 3,
                        'whatsapp_messages'  => 10000,
                    ],
                ],
                'business' => [
                    'price' => 33000,
                    'limits' => [
                        'crm_users'          => 10,
                        'crm_contacts'       => 100000,
                        'emails_per_month'   => 200000,
                        'whatsapp_numbers'   => 10,
                        'whatsapp_messages'  => 50000,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test Starter fixed plan price.
     */
    public function test_starter_fixed_plan_price()
    {
        $pricing = $this->controller->recalculatePricing('starter', 1, 5000, 10000, 1, 1000);
        $this->assertEquals(2200, $pricing['base_price']);
        $this->assertEquals(2200, $pricing['subtotal']);
        // 2200 + 18% tax (396) = 2596
        $this->assertEquals(396, $pricing['tax_amount']);
        $this->assertEquals(2596, $pricing['grand_total']);
    }

    /**
     * Test Growth fixed plan price.
     */
    public function test_growth_fixed_plan_price()
    {
        $pricing = $this->controller->recalculatePricing('growth', 5, 25000, 50000, 3, 10000);
        $this->assertEquals(10000, $pricing['base_price']);
        $this->assertEquals(10000, $pricing['subtotal']);
        // 10000 + 18% tax (1800) = 11800
        $this->assertEquals(1800, $pricing['tax_amount']);
        $this->assertEquals(11800, $pricing['grand_total']);
    }

    /**
     * Test Business fixed plan price.
     */
    public function test_business_fixed_plan_price()
    {
        $pricing = $this->controller->recalculatePricing('business', 10, 100000, 200000, 10, 50000);
        $this->assertEquals(33000, $pricing['base_price']);
        $this->assertEquals(33000, $pricing['subtotal']);
        // 33000 + 18% tax (5940) = 38940
        $this->assertEquals(5940, $pricing['tax_amount']);
        $this->assertEquals(38940, $pricing['grand_total']);
    }

    /**
     * Test Custom plan calculated pricing.
     */
    public function test_custom_plan_calculated_pricing()
    {
        // 5 crm users * 600 = 3000
        // 10000 contacts = 10 * 10 = 100
        // 25000 emails = 25 * 100 = 2500
        // 2 whatsapp numbers * 500 = 1000
        // 5000 whatsapp messages * 0 = 0
        // Total base = 3000+100+2500+1000 = 6600
        $pricing = $this->controller->recalculatePricing('custom', 5, 10000, 25000, 2, 5000);
        $this->assertEquals(6600, $pricing['base_price']);
        $this->assertEquals(6600, $pricing['subtotal']);
        $this->assertEquals(1188, $pricing['tax_amount']);
        $this->assertEquals(7788, $pricing['grand_total']);
    }

    /**
     * Test Custom plan calculated pricing with unselected modules.
     */
    public function test_custom_plan_calculated_pricing_with_unselected_modules()
    {
        // 5 crm users * 600 = 3000
        // 10000 contacts = 10 * 10 = 100
        // 0 emails = 0
        // 0 whatsapp numbers = 0
        // 0 whatsapp messages = 0
        // Total base = 3100
        $pricing = $this->controller->recalculatePricing('custom', 5, 10000, 0, 0, 0);
        $this->assertEquals(3100, $pricing['base_price']);
        $this->assertEquals(3100, $pricing['subtotal']);
        $this->assertEquals(558, $pricing['tax_amount']);
        $this->assertEquals(3658, $pricing['grand_total']);
    }
}
