<?php

namespace Tests\Feature;

use App\Enums\AgentAvailability;
use App\Enums\ConfirmationStatus;
use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\UserRole;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Jobs\SendOtpSms;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryAgent;
use App\Models\DeliveryFee;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\OtpService;
use App\Services\PayoutService;
use App\Services\ReconciliationService;
use App\Services\VendorOrderService;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_cod_order_lifecycle_from_checkout_to_vendor_payout(): void
    {
        $this->seed(NigeriaGeographySeeder::class);

        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();

        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Electronics',
            'slug' => 'test-electronics',
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
            'approved_at' => now(),
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Phone',
            'slug' => 'test-phone',
            'base_price' => 5000000, // 50,000 naira in kobo
            'stock' => 10,
            'status' => 'published',
        ]);

        $zone = DeliveryZone::create(['name' => 'Ikeja Zone', 'state_id' => $lagos->id, 'lga_id' => $ikeja->id]);
        DeliveryFee::create(['delivery_zone_id' => $zone->id, 'vendor_id' => null, 'fee' => 150000]); // 1,500 naira

        $agentUser = User::factory()->agent()->create();
        $agent = DeliveryAgent::create([
            'user_id' => $agentUser->id,
            'state_id' => $lagos->id,
            'lga_id' => $ikeja->id,
            'availability' => AgentAvailability::Available,
        ]);

        $customer = User::factory()->create(['phone' => '+2348099998888']);
        $address = Address::create([
            'user_id' => $customer->id,
            'state_id' => $lagos->id,
            'lga_id' => $ikeja->id,
            'area' => 'Allen Avenue',
            'street_address' => '10 Test Close',
            'phone' => '+2348099998888',
            'is_default' => true,
        ]);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2]);

        // --- Checkout ---
        Bus::fake([SendOtpSms::class]);
        Mail::fake();

        $order = app(CheckoutService::class)->placeOrder($customer, $cart->fresh('items'), $address);

        $this->assertSame(10000000, $order->cod_amount_expected);
        $this->assertSame(150000, $order->delivery_fee_total);
        $this->assertCount(1, $order->vendorOrders);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertTrue($cart->fresh()->items->isEmpty());

        $capturedCode = null;
        Bus::assertDispatched(SendOtpSms::class, function (SendOtpSms $job) use (&$capturedCode, $address) {
            $capturedCode = $job->code;

            return $job->phone === $address->phone;
        });
        $this->assertNotNull($capturedCode);

        // --- Delivery fee paid via OPay before the order can be confirmed ---
        \Illuminate\Support\Facades\Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/status' => \Illuminate\Support\Facades\Http::response([
                'code' => '00000',
                'data' => ['status' => 'SUCCESS'],
            ], 200),
        ]);

        $deliveryFeePayment = \App\Models\DeliveryFeePayment::create([
            'order_id' => $order->id,
            'reference' => 'delfee_test_ref',
            'amount' => $order->delivery_fee_total,
            'status' => \App\Enums\DeliveryFeePaymentStatus::Pending,
        ]);
        app(\App\Services\DeliveryFeePaymentService::class)->verifyAndActivate('delfee_test_ref');

        $order->refresh();
        $this->assertTrue($order->deliveryFeePaid());

        // --- OTP verification ---
        $otpService = app(OtpService::class);
        $this->assertFalse($otpService->verify($address->phone, 'order_confirmation', '000000'));
        $this->assertTrue($otpService->verify($address->phone, 'order_confirmation', $capturedCode));

        app(OrderService::class)->confirmFromOtp($order);
        $order->refresh();
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame(ConfirmationStatus::Confirmed, $order->confirmation_status);

        Mail::assertQueued(\App\Mail\OrderConfirmedMail::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->order->is($order));

        // --- Vendor fulfils the order ---
        $vendorOrderService = app(VendorOrderService::class);
        $vendorOrder = $order->vendorOrders()->first();

        $vendorOrderService->accept($vendorOrder);
        Mail::assertQueued(\App\Mail\VendorOrderAcceptedMail::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->vendorOrder->is($vendorOrder));

        $vendorOrderService->pack($vendorOrder);
        $vendorOrderService->assignAgent($vendorOrder, $agent);
        $vendorOrderService->markOutForDelivery($vendorOrder);

        $vendorOrder->refresh();
        $this->assertSame(VendorOrderStatus::OutForDelivery, $vendorOrder->status);
        $this->assertSame($agent->id, $vendorOrder->delivery_agent_id);

        Mail::assertQueued(\App\Mail\AgentAssignedToDeliveryMail::class, fn ($mail) => $mail->hasTo($agentUser->email) && $mail->vendorOrder->is($vendorOrder));

        // --- Delivery + cash collection (delivery fee already paid via OPay, so only items are cash) ---
        $reconciliation = $vendorOrderService->markDelivered($vendorOrder, $vendorOrder->cashDueAtDelivery());

        $vendorOrder->refresh();
        $order->refresh();
        $this->assertSame(VendorOrderStatus::Delivered, $vendorOrder->status);
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame(ReconciliationStatus::Collected, $reconciliation->status);
        $this->assertSame($vendorOrder->cashDueAtDelivery(), $order->cod_amount_collected);

        Mail::assertQueued(\App\Mail\CashCollectedMail::class, fn ($mail) => $mail->hasTo($vendorUser->email) && $mail->vendorOrder->is($vendorOrder));

        // --- Cash reconciliation / remittance ---
        app(ReconciliationService::class)->remit($reconciliation, $reconciliation->amount_collected);
        $reconciliation->refresh();
        $this->assertSame(ReconciliationStatus::Remitted, $reconciliation->status);

        Mail::assertQueued(\App\Mail\CashRemittedMail::class, fn ($mail) => $mail->hasTo($vendorUser->email) && $mail->reconciliation->is($reconciliation));

        // --- Vendor payout ---
        $payout = app(PayoutService::class)->generateForVendor($vendor, now()->subDay(), now()->addDay());
        $this->assertSame($vendorOrder->items_subtotal, $payout->total_amount);
        $this->assertSame(PayoutStatus::Pending, $payout->status);

        app(PayoutService::class)->markPaid($payout, 'REF-TEST-1');
        $payout->refresh();
        $this->assertSame(PayoutStatus::Paid, $payout->status);
        $this->assertNotNull($payout->paid_at);

        Mail::assertQueued(\App\Mail\VendorPayoutPaidMail::class, fn ($mail) => $mail->hasTo($vendorUser->email) && $mail->payout->is($payout));
    }

    public function test_delivery_failure_restocks_and_cancels_after_max_attempts(): void
    {
        config(['markethub.max_delivery_attempts' => 2]);

        $this->seed(NigeriaGeographySeeder::class);
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Electronics',
            'slug' => 'test-electronics-2',
            'business_phone' => '+2348012340001',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Phone 2',
            'slug' => 'test-phone-2',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => 'published',
        ]);

        $zone = DeliveryZone::create(['name' => 'Ikeja Zone', 'state_id' => $lagos->id, 'lga_id' => $ikeja->id]);
        DeliveryFee::create(['delivery_zone_id' => $zone->id, 'vendor_id' => null, 'fee' => 100000]);

        $customer = User::factory()->create(['phone' => '+2348099990000']);
        $address = Address::create([
            'user_id' => $customer->id,
            'state_id' => $lagos->id,
            'lga_id' => $ikeja->id,
            'area' => 'Allen Avenue',
            'street_address' => '10 Test Close',
            'phone' => '+2348099990000',
            'is_default' => true,
        ]);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        Bus::fake([SendOtpSms::class]);
        Mail::fake();
        $order = app(CheckoutService::class)->placeOrder($customer, $cart->fresh('items'), $address);
        $this->assertSame(4, $product->fresh()->stock);

        $vendorOrderService = app(VendorOrderService::class);
        $vendorOrder = $order->vendorOrders()->first();

        $vendorOrderService->markFailed($vendorOrder, \App\Enums\DeliveryFailureReason::NoCash);
        $vendorOrder->refresh();
        $this->assertSame(1, $vendorOrder->delivery_attempts);
        $this->assertSame(VendorOrderStatus::Packed, $vendorOrder->status);
        $this->assertSame(4, $product->fresh()->stock, 'Stock should not be restored before max attempts.');

        Mail::assertQueued(\App\Mail\DeliveryFailedMail::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->vendorOrder->is($vendorOrder));

        $vendorOrderService->markFailed($vendorOrder, \App\Enums\DeliveryFailureReason::NoCash);
        $vendorOrder->refresh();
        $order->refresh();
        $this->assertSame(2, $vendorOrder->delivery_attempts);
        $this->assertSame(VendorOrderStatus::Failed, $vendorOrder->status);
        $this->assertSame(5, $product->fresh()->stock, 'Stock should be restored after max attempts reached.');
        $this->assertSame(OrderStatus::Cancelled, $order->status);
    }

    public function test_checkout_rejects_blacklisted_phone_numbers(): void
    {
        $this->seed(NigeriaGeographySeeder::class);
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Electronics',
            'slug' => 'test-electronics-3',
            'business_phone' => '+2348012340002',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Phone 3',
            'slug' => 'test-phone-3',
            'base_price' => 1000000,
            'stock' => 5,
            'status' => 'published',
        ]);

        \App\Models\BlacklistedNumber::create([
            'phone' => '+2348099991111',
            'reason' => 'Repeated fake orders',
            'blocked_at' => now(),
        ]);

        $customer = User::factory()->create(['phone' => '+2348099991111']);
        $address = Address::create([
            'user_id' => $customer->id,
            'state_id' => $lagos->id,
            'lga_id' => $ikeja->id,
            'area' => 'Allen Avenue',
            'street_address' => '10 Test Close',
            'phone' => '+2348099991111',
            'is_default' => true,
        ]);

        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->expectException(\App\Exceptions\CustomerBlacklistedException::class);
        app(CheckoutService::class)->placeOrder($customer, $cart->fresh('items'), $address);
    }
}
