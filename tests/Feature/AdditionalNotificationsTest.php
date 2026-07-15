<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway;
use App\Enums\ProductStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Jobs\SendOtpSms;
use App\Livewire\Admin\ProductApprovals;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderRejectedMail;
use App\Mail\ProductApprovedMail;
use App\Mail\ProductRejectedMail;
use App\Mail\SubscriptionActivatedMail;
use App\Mail\SubscriptionExpiredMail;
use App\Mail\VendorOrderRejectedMail;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryFee;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\SubscriptionService;
use App\Services\VendorOrderService;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdditionalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(string $suffix = '1'): Vendor
    {
        return Vendor::create([
            'user_id' => User::factory()->vendor()->create()->id,
            'business_name' => 'Test Vendor '.$suffix,
            'slug' => 'test-vendor-'.$suffix,
            'business_phone' => '+234800000'.str_pad($suffix, 4, '0', STR_PAD_LEFT),
            'business_address' => 'Address',
            'status' => VendorStatus::Approved,
        ]);
    }

    private function makeOrder(Vendor $vendor, Product $product): array
    {
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();

        $zone = DeliveryZone::firstOrCreate(
            ['state_id' => $lagos->id, 'lga_id' => $ikeja->id],
            ['name' => 'Ikeja Zone']
        );
        DeliveryFee::firstOrCreate(
            ['delivery_zone_id' => $zone->id, 'vendor_id' => null],
            ['fee' => 150000]
        );

        $customer = User::factory()->create(['phone' => '+2348077770000']);
        $address = Address::create([
            'user_id' => $customer->id,
            'state_id' => $lagos->id,
            'lga_id' => $ikeja->id,
            'area' => 'Allen Avenue',
            'street_address' => '10 Test Close',
            'phone' => '+2348077770000',
            'is_default' => true,
        ]);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        Bus::fake([SendOtpSms::class]);
        $order = app(CheckoutService::class)->placeOrder($customer, $cart->fresh('items'), $address);

        return [$order, $customer];
    }

    public function test_admin_approving_a_product_notifies_the_vendor(): void
    {
        Mail::fake();

        $vendor = $this->makeVendor();
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Notify Phone',
            'slug' => 'notify-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::PendingReview,
        ]);

        Livewire::actingAs($admin)->test(ProductApprovals::class)->call('approve', $product->id);

        Mail::assertQueued(ProductApprovedMail::class, fn ($mail) => $mail->hasTo($vendor->user->email) && $mail->product->is($product));
    }

    public function test_admin_rejecting_a_product_notifies_the_vendor(): void
    {
        Mail::fake();

        $vendor = $this->makeVendor();
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Reject Phone',
            'slug' => 'reject-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::PendingReview,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductApprovals::class)
            ->set("rejectionReason.{$product->id}", 'Photo mismatch')
            ->call('reject', $product->id);

        Mail::assertQueued(ProductRejectedMail::class, fn ($mail) => $mail->hasTo($vendor->user->email) && $mail->product->is($product));
    }

    public function test_rejecting_an_order_notifies_the_customer(): void
    {
        $this->seed(NigeriaGeographySeeder::class);
        Mail::fake();

        $vendor = $this->makeVendor('2');
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Reject Order Phone',
            'slug' => 'reject-order-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::Published,
        ]);

        [$order, $customer] = $this->makeOrder($vendor, $product);

        app(OrderService::class)->reject($order, 'Suspected fraud');

        Mail::assertQueued(OrderRejectedMail::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->order->is($order));
        $this->assertSame(5, $product->fresh()->stock, 'Rejecting must restock.');
    }

    public function test_cancelling_an_order_notifies_the_customer(): void
    {
        $this->seed(NigeriaGeographySeeder::class);
        Mail::fake();

        $vendor = $this->makeVendor('3');
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Cancel Order Phone',
            'slug' => 'cancel-order-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::Published,
        ]);

        [$order, $customer] = $this->makeOrder($vendor, $product);

        app(OrderService::class)->cancel($order, 'Customer requested cancellation');

        Mail::assertQueued(OrderCancelledMail::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->order->is($order));
    }

    public function test_vendor_rejecting_their_leg_of_an_order_notifies_the_customer(): void
    {
        $this->seed(NigeriaGeographySeeder::class);
        Mail::fake();

        $vendor = $this->makeVendor('4');
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Vendor Reject Phone',
            'slug' => 'vendor-reject-phone',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => ProductStatus::Published,
        ]);

        [$order, $customer] = $this->makeOrder($vendor, $product);
        $vendorOrder = $order->vendorOrders()->first();

        app(VendorOrderService::class)->reject($vendorOrder, 'Out of stock');

        $vendorOrder->refresh();
        $this->assertSame(VendorOrderStatus::Rejected, $vendorOrder->status);
        Mail::assertQueued(VendorOrderRejectedMail::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->vendorOrder->is($vendorOrder));
    }

    public function test_activating_a_subscription_notifies_the_vendor(): void
    {
        Mail::fake();

        $vendor = $this->makeVendor('5');

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'gateway' => PaymentGateway::Paystack,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_test_ref',
        ]);

        app(SubscriptionService::class)->activate($subscription);

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        Mail::assertQueued(SubscriptionActivatedMail::class, fn ($mail) => $mail->hasTo($vendor->user->email) && $mail->subscription->is($subscription));
    }

    public function test_expire_command_notifies_vendors_whose_subscription_lapsed(): void
    {
        Mail::fake();

        $vendor = $this->makeVendor('6');

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'gateway' => PaymentGateway::Paystack,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Active,
            'reference' => 'sub_test_ref_2',
            'paid_at' => now()->subMonth(),
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('app:expire-vendor-subscriptions')->assertExitCode(0);

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Expired, $subscription->status);
        Mail::assertQueued(SubscriptionExpiredMail::class, fn ($mail) => $mail->hasTo($vendor->user->email) && $mail->subscription->is($subscription));
    }
}
