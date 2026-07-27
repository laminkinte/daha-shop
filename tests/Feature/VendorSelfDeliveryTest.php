<?php

namespace Tests\Feature;

use App\Enums\DeliveryPaymentStatus;
use App\Enums\PaymentGateway;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Livewire\Admin\DispatchBoard;
use App\Livewire\Vendor\OrderManager;
use App\Models\Address;
use App\Models\Category;
use App\Models\DeliveryPayment;
use App\Models\Order;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\PayoutService;
use App\Services\VendorOrderService;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class VendorSelfDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function makePackedVendorOrder(): VendorOrder
    {
        $this->seed(NigeriaGeographySeeder::class);
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Electronics',
            'slug' => 'test-electronics',
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street, Ikeja',
            'status' => VendorStatus::Approved,
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Phone',
            'slug' => 'test-phone',
            'base_price' => 5000000,
            'stock' => 10,
            'status' => 'published',
        ]);

        $state = State::first();
        $lga = $state->lgas()->first();
        $customer = User::factory()->create();

        $order = Order::create([
            'order_number' => 'TESTSELFDEL1',
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
            'items_subtotal' => 5000000,
            'delivery_fee_total' => 0,
            'cod_amount_expected' => 5000000,
        ]);

        $vendorOrder = VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'status' => VendorOrderStatus::Accepted,
            'items_subtotal' => 5000000,
            'delivery_fee' => 0,
        ]);

        app(VendorOrderService::class)->pack($vendorOrder);

        return $vendorOrder->fresh();
    }

    public function test_vendor_can_deliver_a_packed_order_themselves(): void
    {
        $vendorOrder = $this->makePackedVendorOrder();
        $vendor = $vendorOrder->vendor;

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->call('deliverMyself', $vendorOrder->id);

        $vendorOrder->refresh();
        $this->assertSame(VendorOrderStatus::OutForDelivery, $vendorOrder->status);
        $this->assertNull($vendorOrder->delivery_agent_id);
        $this->assertNotNull($vendorOrder->out_for_delivery_at);
    }

    public function test_self_delivering_order_disappears_from_the_admin_dispatch_board(): void
    {
        $vendorOrder = $this->makePackedVendorOrder();
        $vendor = $vendorOrder->vendor;

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->call('deliverMyself', $vendorOrder->id);

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(DispatchBoard::class)
            ->assertDontSee((string) $vendorOrder->order->order_number);
    }

    public function test_vendor_completes_self_delivery_via_digital_payment_and_becomes_payout_eligible(): void
    {
        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/create' => Http::response([
                'code' => '00000',
                'data' => ['reference' => 'x', 'cashierUrl' => 'https://sandboxcashier.opaycheckout.com/checkout/selfdel1', 'status' => 'INITIAL'],
            ], 200),
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/status' => Http::response([
                'code' => '00000',
                'data' => ['status' => 'SUCCESS'],
            ], 200),
        ]);

        $vendorOrder = $this->makePackedVendorOrder();
        $vendor = $vendorOrder->vendor;

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->call('deliverMyself', $vendorOrder->id);

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->call('startDigitalPayment', $vendorOrder->id)
            ->assertHasNoErrors();

        $payment = DeliveryPayment::where('vendor_order_id', $vendorOrder->id)->firstOrFail();
        $this->assertSame(DeliveryPaymentStatus::Pending, $payment->status);

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->call('refreshPendingPayments');

        $vendorOrder->refresh();
        $this->assertSame(VendorOrderStatus::Delivered, $vendorOrder->status);
        $this->assertSame(DeliveryPaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(0, $vendorOrder->cash_collected);
        $this->assertNotNull($vendorOrder->delivered_at);
        $this->assertNull($vendorOrder->cashReconciliation);

        $payout = app(PayoutService::class)->generateForVendor($vendor, now()->subDay(), now()->addDay());

        $this->assertSame($vendorOrder->items_subtotal, $payout->total_amount);
        $this->assertSame($payout->id, $vendorOrder->fresh()->vendor_payout_id);
    }

    /**
     * Regression test: without real OPay merchant credentials configured
     * (the normal case for local dev), starting a digital payment used to
     * throw and no QR ever rendered on the vendor's order list either. The
     * vendor can now select the local-only Test gateway per-order and
     * complete the flow with no external API calls at all.
     */
    public function test_vendor_completes_self_delivery_payment_using_the_local_test_gateway(): void
    {
        $vendorOrder = $this->makePackedVendorOrder();
        $vendor = $vendorOrder->vendor;

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->call('deliverMyself', $vendorOrder->id);

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->set("gateway.{$vendorOrder->id}", 'test')
            ->call('startDigitalPayment', $vendorOrder->id)
            ->assertHasNoErrors();

        $payment = DeliveryPayment::where('vendor_order_id', $vendorOrder->id)->firstOrFail();
        $this->assertSame(PaymentGateway::Test, $payment->gateway);
        $this->assertNotNull($payment->cashier_url);

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->call('refreshPendingPayments');

        $vendorOrder->refresh();
        $this->assertSame(VendorOrderStatus::Delivered, $vendorOrder->status);
        $this->assertSame(DeliveryPaymentStatus::Paid, $payment->fresh()->status);
    }
}
