<?php

namespace Tests\Feature;

use App\Enums\AgentAvailability;
use App\Enums\ReconciliationStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Exceptions\NothingToPayoutException;
use App\Models\CashReconciliation;
use App\Models\Category;
use App\Models\DeliveryAgent;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\PayoutService;
use App\Services\ReconciliationService;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeDeliveredVendorOrder(): VendorOrder
    {
        $this->seed(NigeriaGeographySeeder::class);
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Electronics',
            'slug' => 'test-electronics',
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);

        $state = \App\Models\State::first();
        $lga = $state->lgas()->first();

        $agentUser = User::factory()->agent()->create();
        $agent = DeliveryAgent::create([
            'user_id' => $agentUser->id,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'availability' => AgentAvailability::Available,
        ]);

        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'TESTPAYOUT1',
            'user_id' => $customer->id,
            'address_id' => \App\Models\Address::create([
                'user_id' => $customer->id,
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'label' => 'Home',
                'area' => 'Area',
                'street_address' => 'Street',
                'phone' => '+2348000000000',
            ])->id,
            'items_subtotal' => 5000000,
            'delivery_fee_total' => 150000,
            'delivery_fee_paid_at' => now(),
            'cod_amount_expected' => 5000000,
        ]);

        return VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'delivery_agent_id' => $agent->id,
            'status' => VendorOrderStatus::Delivered,
            'items_subtotal' => 5000000,
            'delivery_fee' => 150000,
            'delivered_at' => now(),
        ]);
    }

    public function test_delivered_order_is_not_payout_eligible_until_cash_is_remitted(): void
    {
        $vendorOrder = $this->makeDeliveredVendorOrder();

        CashReconciliation::create([
            'delivery_agent_id' => $vendorOrder->delivery_agent_id,
            'vendor_order_id' => $vendorOrder->id,
            'amount_expected' => $vendorOrder->items_subtotal,
            'amount_collected' => $vendorOrder->items_subtotal,
            'status' => ReconciliationStatus::Collected,
        ]);

        $this->expectException(NothingToPayoutException::class);

        app(PayoutService::class)->generateForVendor($vendorOrder->vendor, now()->subDay(), now()->addDay());
    }

    public function test_delivered_order_becomes_payout_eligible_once_remitted(): void
    {
        $vendorOrder = $this->makeDeliveredVendorOrder();

        $reconciliation = CashReconciliation::create([
            'delivery_agent_id' => $vendorOrder->delivery_agent_id,
            'vendor_order_id' => $vendorOrder->id,
            'amount_expected' => $vendorOrder->items_subtotal,
            'amount_collected' => $vendorOrder->items_subtotal,
            'status' => ReconciliationStatus::Collected,
        ]);

        app(ReconciliationService::class)->remit($reconciliation, $reconciliation->amount_collected);

        $payout = app(PayoutService::class)->generateForVendor($vendorOrder->vendor, now()->subDay(), now()->addDay());

        $this->assertSame($vendorOrder->items_subtotal, $payout->total_amount);
        $this->assertSame($vendorOrder->id, $vendorOrder->fresh()->vendor_payout_id);
    }

    public function test_a_short_remittance_still_blocks_payout(): void
    {
        $vendorOrder = $this->makeDeliveredVendorOrder();

        CashReconciliation::create([
            'delivery_agent_id' => $vendorOrder->delivery_agent_id,
            'vendor_order_id' => $vendorOrder->id,
            'amount_expected' => $vendorOrder->items_subtotal,
            'amount_collected' => $vendorOrder->items_subtotal - 100000,
            'status' => ReconciliationStatus::Short,
        ]);

        $this->expectException(NothingToPayoutException::class);

        app(PayoutService::class)->generateForVendor($vendorOrder->vendor, now()->subDay(), now()->addDay());
    }
}
