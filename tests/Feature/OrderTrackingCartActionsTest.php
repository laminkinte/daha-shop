<?php

namespace Tests\Feature;

use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Livewire\Storefront\OrderTracking;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTrackingCartActionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeDeliveredVendorOrderWithItems(): VendorOrder
    {
        if (State::count() === 0) {
            $this->seed(NigeriaGeographySeeder::class);
        }
        $category = Category::firstOrCreate(['slug' => 'phones'], ['name' => 'Phones']);
        $state = State::first();
        $lga = $state->lgas()->first();

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Electronics',
            'slug' => 'test-electronics-'.uniqid(),
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Phone',
            'slug' => 'test-phone-'.uniqid(),
            'base_price' => 5000000,
            'stock' => 10,
            'status' => 'published',
        ]);

        $customer = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD'.uniqid(),
            'user_id' => $customer->id,
            'address_id' => Address::create([
                'user_id' => $customer->id,
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'label' => 'Home',
                'area' => 'Area',
                'street_address' => 'Street',
                'phone' => '+2348099998888',
            ])->id,
            'items_subtotal' => 5000000,
            'delivery_fee_total' => 0,
            'cod_amount_expected' => 5000000,
        ]);

        $vendorOrder = VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'status' => VendorOrderStatus::Delivered,
            'items_subtotal' => 5000000,
            'delivery_fee' => 0,
        ]);

        OrderItem::create([
            'vendor_order_id' => $vendorOrder->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 5000000,
            'subtotal' => 10000000,
        ]);

        return $vendorOrder->fresh();
    }

    public function test_add_to_cart_adds_this_vendor_orders_items(): void
    {
        $vendorOrder = $this->makeDeliveredVendorOrderWithItems();
        $item = $vendorOrder->items->first();

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->call('addToCart', $vendorOrder->id)
            ->assertDispatched('cart-updated');

        $cart = Cart::where('user_id', $vendorOrder->order->user_id)->firstOrFail();
        $cartItem = $cart->items()->where('product_id', $item->product_id)->first();

        $this->assertNotNull($cartItem);
        $this->assertSame(2, $cartItem->quantity);
    }

    public function test_buy_now_and_add_to_cart_buttons_show_on_a_delivered_non_tracked_order(): void
    {
        $vendorOrder = $this->makeDeliveredVendorOrderWithItems();

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->assertSee('Buy Now')
            ->assertSee('Add to Cart');
    }

    public function test_add_to_cart_accumulates_quantity_on_repeat_calls(): void
    {
        $vendorOrder = $this->makeDeliveredVendorOrderWithItems();
        $item = $vendorOrder->items->first();

        $test = Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order]);

        $test->call('addToCart', $vendorOrder->id);
        $test->call('addToCart', $vendorOrder->id);

        $cart = Cart::where('user_id', $vendorOrder->order->user_id)->firstOrFail();
        $cartItem = $cart->items()->where('product_id', $item->product_id)->first();

        $this->assertSame(4, $cartItem->quantity);
    }

    public function test_add_to_cart_rejects_a_vendor_order_not_on_this_order(): void
    {
        $vendorOrder = $this->makeDeliveredVendorOrderWithItems();
        $otherVendorOrder = $this->makeDeliveredVendorOrderWithItems();

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->call('addToCart', $otherVendorOrder->id)
            ->assertStatus(404);
    }

    public function test_buy_now_adds_to_cart_and_redirects_to_checkout(): void
    {
        $vendorOrder = $this->makeDeliveredVendorOrderWithItems();
        $item = $vendorOrder->items->first();

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->call('buyNow', $vendorOrder->id)
            ->assertRedirect(route('storefront.checkout'));

        $cart = Cart::where('user_id', $vendorOrder->order->user_id)->firstOrFail();
        $this->assertNotNull($cart->items()->where('product_id', $item->product_id)->first());
    }
}
