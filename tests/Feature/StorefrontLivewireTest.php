<?php

namespace Tests\Feature;

use App\Enums\VendorStatus;
use App\Jobs\SendOtpSms;
use App\Livewire\Storefront\Cart;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\OtpVerify;
use App\Livewire\Storefront\ProductCatalog;
use App\Livewire\Storefront\ProductDetail;
use App\Models\Category;
use App\Models\DeliveryFee;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontLivewireTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
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

        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();
        $zone = DeliveryZone::create(['name' => 'Ikeja Zone', 'state_id' => $lagos->id, 'lga_id' => $ikeja->id]);
        DeliveryFee::create(['delivery_zone_id' => $zone->id, 'vendor_id' => null, 'fee' => 150000]);

        return Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Phone',
            'slug' => 'test-phone',
            'base_price' => 5000000,
            'stock' => 10,
            'status' => 'published',
        ]);
    }

    public function test_catalog_lists_published_products_and_add_to_cart_works(): void
    {
        $product = $this->makeProduct();

        Livewire::test(ProductCatalog::class)
            ->assertSee('Test Phone')
            ->call('addToCart', $product->id)
            ->assertDispatched('cart-updated');

        Livewire::test(Cart::class)->assertSee('Test Phone');
    }

    public function test_product_detail_add_to_cart_and_wishlist_toggle(): void
    {
        $product = $this->makeProduct();
        $customer = User::factory()->create();

        Livewire::actingAs($customer)
            ->test(ProductDetail::class, ['product' => $product])
            ->assertSee('Test Phone')
            ->call('addToCart')
            ->assertDispatched('cart-updated')
            ->call('toggleWishlist')
            ->assertSet('inWishlist', true);
    }

    public function test_checkout_flow_places_order_and_otp_verification_confirms_it(): void
    {
        $product = $this->makeProduct();
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
            ->call('placeOrder')
            ->assertRedirect();

        $order = \App\Models\Order::firstOrFail();
        $this->assertSame($customer->id, $order->user_id);

        $capturedCode = null;
        Bus::assertDispatched(SendOtpSms::class, function (SendOtpSms $job) use (&$capturedCode) {
            $capturedCode = $job->code;

            return true;
        });

        Livewire::actingAs($customer)
            ->test(OtpVerify::class, ['order' => $order])
            ->set('code', '000000')
            ->call('verify')
            ->assertSet('message', fn ($message) => ! empty($message));

        Livewire::actingAs($customer)
            ->test(OtpVerify::class, ['order' => $order])
            ->set('code', $capturedCode)
            ->call('verify')
            ->assertRedirect(route('storefront.orders.show', $order->order_number));

        $this->assertSame(\App\Enums\ConfirmationStatus::Confirmed, $order->fresh()->confirmation_status);
    }

    public function test_mobile_bottom_bar_shows_login_and_signup_for_guests(): void
    {
        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertSee('Login')
            ->assertSee('Sign Up')
            ->assertDontSee('Orders')
            ->assertDontSee('Account');
    }

    public function test_mobile_bottom_bar_shows_orders_and_account_for_authenticated_users(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('storefront.home'))
            ->assertOk()
            ->assertSee('Orders')
            ->assertSee('Account')
            ->assertDontSee('Sign Up');
    }
}
