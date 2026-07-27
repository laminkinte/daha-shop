<?php

namespace Tests\Feature;

use App\Enums\DeliveryPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Livewire\Storefront\OrderTracking;
use App\Models\Address;
use App\Models\Category;
use App\Models\DeliveryPayment;
use App\Models\Order;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTrackingScanToPayTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendorOrder(VendorOrderStatus $status): VendorOrder
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
            'order_number' => 'TESTSCAN'.$status->value,
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

        return VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'status' => $status,
            'items_subtotal' => 5000000,
            'delivery_fee' => 0,
        ]);
    }

    public function test_scan_to_pay_link_shows_once_out_for_delivery(): void
    {
        $vendorOrder = $this->makeVendorOrder(VendorOrderStatus::OutForDelivery);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->assertSee('Scan to Pay');
    }

    public function test_scan_to_pay_link_shows_once_ready_for_pickup(): void
    {
        $vendorOrder = $this->makeVendorOrder(VendorOrderStatus::ReadyForPickup);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->assertSee('Scan to Pay');
    }

    public function test_scan_to_pay_link_is_absent_before_out_for_delivery(): void
    {
        $vendorOrder = $this->makeVendorOrder(VendorOrderStatus::Accepted);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->assertDontSee('Scan to Pay');
    }

    public function test_payment_received_shows_instead_once_paid(): void
    {
        $vendorOrder = $this->makeVendorOrder(VendorOrderStatus::OutForDelivery);

        DeliveryPayment::create([
            'vendor_order_id' => $vendorOrder->id,
            'reference' => 'delivery_'.$vendorOrder->id.'_ref',
            'amount' => 5000000,
            'method' => 'qr',
            'status' => DeliveryPaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->assertSee('Payment received')
            ->assertDontSee('Scan to Pay');
    }
}
