<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Jobs\SendOtpSms;
use App\Livewire\Admin\DispatchBoard;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Vendor\OrderManager;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\VendorOrderService;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class PickupOrderTest extends TestCase
{
    use RefreshDatabase;

    private function makeProductAndVendor(): array
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

        return [$vendor, $product];
    }

    public function test_customer_can_choose_pickup_for_a_vendor_with_zero_delivery_fee(): void
    {
        [$vendor, $product] = $this->makeProductAndVendor();
        $customer = User::factory()->create(['phone' => '+2348099998888']);

        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();

        $cart = \App\Models\Cart::create(['user_id' => $customer->id]);
        \App\Models\CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        Bus::fake([SendOtpSms::class]);

        Livewire::actingAs($customer)
            ->test(Checkout::class)
            ->set('useNewAddress', true)
            ->set('stateId', $lagos->id)
            ->set('lgaId', $ikeja->id)
            ->set('area', 'Allen Avenue')
            ->set('streetAddress', '10 Test Close')
            ->set('phone', '+2348099998888')
            ->set("fulfillmentMethods.{$vendor->id}", 'pickup')
            ->call('placeOrder')
            ->assertRedirect();

        $order = Order::firstOrFail();
        $vendorOrder = $order->vendorOrders()->firstOrFail();

        $this->assertSame(FulfillmentMethod::Pickup, $vendorOrder->fulfillment_method);
        $this->assertSame(0, $vendorOrder->delivery_fee);
        $this->assertSame($vendorOrder->items_subtotal, $order->cod_amount_expected);
    }

    public function test_delivery_still_charges_a_fee_when_pickup_not_selected(): void
    {
        [$vendor, $product] = $this->makeProductAndVendor();
        $customer = User::factory()->create(['phone' => '+2348099998888']);

        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();
        $zone = \App\Models\DeliveryZone::create(['name' => 'Ikeja Zone', 'state_id' => $lagos->id, 'lga_id' => $ikeja->id]);
        \App\Models\DeliveryFee::create(['delivery_zone_id' => $zone->id, 'vendor_id' => null, 'fee' => 150000]);

        $cart = \App\Models\Cart::create(['user_id' => $customer->id]);
        \App\Models\CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        Bus::fake([SendOtpSms::class]);

        Livewire::actingAs($customer)
            ->test(Checkout::class)
            ->set('useNewAddress', true)
            ->set('stateId', $lagos->id)
            ->set('lgaId', $ikeja->id)
            ->set('area', 'Allen Avenue')
            ->set('streetAddress', '10 Test Close')
            ->set('phone', '+2348099998888')
            ->call('placeOrder')
            ->assertRedirect();

        $vendorOrder = Order::firstOrFail()->vendorOrders()->firstOrFail();

        $this->assertSame(FulfillmentMethod::Delivery, $vendorOrder->fulfillment_method);
        $this->assertSame(150000, $vendorOrder->delivery_fee);
    }

    private function makePickupVendorOrder(): VendorOrder
    {
        [$vendor, $product] = $this->makeProductAndVendor();
        $customer = User::factory()->create();

        $order = Order::create([
            'order_number' => 'TESTPICKUP1',
            'user_id' => $customer->id,
            'address_id' => \App\Models\Address::create([
                'user_id' => $customer->id,
                'state_id' => State::first()->id,
                'lga_id' => State::first()->lgas()->first()->id,
                'label' => 'Home',
                'area' => 'Area',
                'street_address' => 'Street',
                'phone' => '+2348000000000',
            ])->id,
            'items_subtotal' => 5000000,
            'delivery_fee_total' => 0,
            'cod_amount_expected' => 5000000,
        ]);

        return VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'status' => VendorOrderStatus::Accepted,
            'fulfillment_method' => FulfillmentMethod::Pickup,
            'items_subtotal' => 5000000,
            'delivery_fee' => 0,
        ]);
    }

    public function test_vendor_can_mark_pickup_order_ready_then_confirm_picked_up_with_cash(): void
    {
        $vendorOrder = $this->makePickupVendorOrder();
        $vendor = $vendorOrder->vendor;

        app(VendorOrderService::class)->pack($vendorOrder);

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->call('markReadyForPickup', $vendorOrder->id);

        $this->assertSame(VendorOrderStatus::ReadyForPickup, $vendorOrder->fresh()->status);

        Livewire::actingAs($vendor->user)
            ->test(OrderManager::class)
            ->set("pickupCash.{$vendorOrder->id}", '50000.00')
            ->call('confirmPickedUp', $vendorOrder->id);

        $vendorOrder->refresh();
        $this->assertSame(VendorOrderStatus::PickedUp, $vendorOrder->status);
        $this->assertSame(5000000, $vendorOrder->cash_collected);
        $this->assertNotNull($vendorOrder->picked_up_at);

        $this->assertSame(OrderStatus::Completed, $vendorOrder->order->fresh()->status);
        $this->assertSame(5000000, $vendorOrder->order->fresh()->cod_amount_collected);
    }

    public function test_picked_up_order_creates_no_cash_reconciliation_for_an_agent(): void
    {
        $vendorOrder = $this->makePickupVendorOrder();

        app(VendorOrderService::class)->pack($vendorOrder);
        app(VendorOrderService::class)->markReadyForPickup($vendorOrder);
        app(VendorOrderService::class)->markPickedUp($vendorOrder, 5000000);

        $this->assertNull($vendorOrder->fresh()->cashReconciliation);
        $this->assertNull($vendorOrder->fresh()->delivery_agent_id);
    }

    public function test_dispatch_board_only_shows_packed_delivery_orders_not_pickup(): void
    {
        $pickupOrder = $this->makePickupVendorOrder();
        app(VendorOrderService::class)->pack($pickupOrder);

        $secondOrder = Order::create([
            'order_number' => 'TESTDELIVERY1',
            'user_id' => $pickupOrder->order->user_id,
            'address_id' => $pickupOrder->order->address_id,
            'items_subtotal' => 1000000,
            'delivery_fee_total' => 150000,
            'cod_amount_expected' => 1150000,
        ]);

        $deliveryOrder = VendorOrder::create([
            'order_id' => $secondOrder->id,
            'vendor_id' => $pickupOrder->vendor_id,
            'status' => VendorOrderStatus::Packed,
            'fulfillment_method' => FulfillmentMethod::Delivery,
            'items_subtotal' => 1000000,
            'delivery_fee' => 150000,
        ]);

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(DispatchBoard::class)
            ->assertDontSee((string) $pickupOrder->order->order_number)
            ->assertSee((string) $deliveryOrder->order->order_number);
    }
}
