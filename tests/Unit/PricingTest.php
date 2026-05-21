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
        // 10000 contacts = 10 * 20 = 200
        // 25000 emails = 25 * 100 = 2500
        // 2 whatsapp numbers * 500 = 1000
        // 5000 whatsapp messages * 0 = 0
        // Total base = 3000+200+2500+1000 = 6700
        $pricing = $this->controller->recalculatePricing('custom', 5, 10000, 25000, 2, 5000);
        $this->assertEquals(6700, $pricing['base_price']);
        $this->assertEquals(6700, $pricing['subtotal']);
        $this->assertEquals(1206, $pricing['tax_amount']);
        $this->assertEquals(7906, $pricing['grand_total']);
    }
}
