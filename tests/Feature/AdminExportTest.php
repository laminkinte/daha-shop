<?php

namespace Tests\Feature;

use App\Enums\AgentAvailability;
use App\Enums\ConfirmationStatus;
use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Models\Address;
use App\Models\CashReconciliation;
use App\Models\DeliveryAgent;
use App\Models\Order;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayout;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $this->seed(NigeriaGeographySeeder::class);
        $state = State::first();
        $lga = $state->lgas()->first();
        $customer = User::factory()->create();

        return Order::create([
            'order_number' => 'ORD'.uniqid(),
            'user_id' => $customer->id,
            'address_id' => Address::create([
                'user_id' => $customer->id,
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'label' => 'Home',
                'area' => 'Area',
                'street_address' => 'Street',
                'phone' => '+2348000000000',
            ])->id,
            'status' => OrderStatus::Completed,
            'confirmation_status' => ConfirmationStatus::Confirmed,
            'items_subtotal' => 500000,
            'delivery_fee_total' => 0,
            'cod_amount_expected' => 500000,
            'cod_amount_collected' => 500000,
        ]);
    }

    public function test_orders_export_returns_a_csv_with_expected_rows(): void
    {
        $this->makeOrder();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.orders.export', ['filter' => 'all']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Order Number', $content);
        $this->assertStringContainsString('5000.00', $content);
    }

    public function test_orders_export_is_permission_gated(): void
    {
        $scopedAdmin = User::factory()->scopedAdmin(['products'])->create();

        $this->actingAs($scopedAdmin)->get(route('admin.orders.export'))->assertForbidden();
    }

    public function test_reconciliation_export_returns_a_csv_with_expected_rows(): void
    {
        $order = $this->makeOrder();
        $state = State::first();
        $lga = $state->lgas()->first();

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Shop',
            'slug' => 'test-shop-'.uniqid(),
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);

        $agentUser = User::factory()->agent()->create();
        $agent = DeliveryAgent::create([
            'user_id' => $agentUser->id,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'availability' => AgentAvailability::Available,
        ]);

        $vendorOrder = VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'delivery_agent_id' => $agent->id,
            'status' => VendorOrderStatus::Delivered,
            'items_subtotal' => 500000,
            'delivery_fee' => 0,
            'delivered_at' => now(),
        ]);

        CashReconciliation::create([
            'delivery_agent_id' => $agent->id,
            'vendor_order_id' => $vendorOrder->id,
            'amount_expected' => 500000,
            'amount_collected' => 500000,
            'status' => ReconciliationStatus::Collected,
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.reconciliation.export', ['filter' => 'all']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString($agentUser->name, $content);
        $this->assertStringContainsString($order->order_number, $content);
    }

    public function test_payouts_export_returns_a_csv_with_expected_rows(): void
    {
        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Payout Shop',
            'slug' => 'payout-shop-'.uniqid(),
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);

        VendorPayout::create([
            'vendor_id' => $vendor->id,
            'period_start' => now()->subDays(30),
            'period_end' => now(),
            'total_amount' => 500000,
            'status' => PayoutStatus::Paid,
            'reference' => 'PAYOUT-REF-1',
            'paid_at' => now(),
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.payouts.export', ['filter' => 'all']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Payout Shop', $content);
        $this->assertStringContainsString('PAYOUT-REF-1', $content);
    }
}
