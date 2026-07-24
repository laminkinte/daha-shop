<?php

namespace Tests\Feature;

use App\Enums\ConfirmationStatus;
use App\Enums\DeliveryFeePaymentStatus;
use App\Enums\VendorStatus;
use App\Jobs\SendOtpSms;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\OtpVerify;
use App\Models\Category;
use App\Models\DeliveryFee;
use App\Models\DeliveryFeePayment;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Services\DeliveryFeePaymentService;
use App\Services\OrderService;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryFeePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrderWithDeliveryFee(): Order
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

        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();
        $zone = DeliveryZone::create(['name' => 'Ikeja Zone', 'state_id' => $lagos->id, 'lga_id' => $ikeja->id]);
        DeliveryFee::create(['delivery_zone_id' => $zone->id, 'vendor_id' => null, 'fee' => 150000]);

        $customer = User::factory()->create(['phone' => '+2348099998888']);
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
            ->call('placeOrder');

        return Order::firstOrFail();
    }

    public function test_order_with_delivery_fee_cannot_confirm_until_it_is_paid(): void
    {
        $order = $this->placeOrderWithDeliveryFee();

        $this->assertSame(150000, $order->delivery_fee_total);
        $this->assertFalse($order->deliveryFeePaid());

        app(OrderService::class)->confirmFromOtp($order);

        $this->assertSame(ConfirmationStatus::PendingConfirmation, $order->fresh()->confirmation_status);
    }

    public function test_paying_delivery_fee_via_opay_unlocks_order_confirmation(): void
    {
        $order = $this->placeOrderWithDeliveryFee();

        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/create' => Http::response([
                'code' => '00000',
                'data' => ['reference' => 'delfee_x', 'cashierUrl' => 'https://sandboxcashier.opaycheckout.com/checkout/x', 'status' => 'INITIAL'],
            ], 200),
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/status' => Http::response([
                'code' => '00000',
                'data' => ['status' => 'SUCCESS'],
            ], 200),
        ]);

        Livewire::actingAs($order->user)
            ->test(OtpVerify::class, ['order' => $order])
            ->call('payDeliveryFee')
            ->assertRedirect('https://sandboxcashier.opaycheckout.com/checkout/x');

        $payment = DeliveryFeePayment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(DeliveryFeePaymentStatus::Pending, $payment->status);
        $this->assertSame(150000, $payment->amount);

        $this->actingAs($order->user)
            ->get(route('storefront.orders.delivery-fee.callback', ['order' => $order->order_number, 'reference' => $payment->reference]))
            ->assertRedirect(route('storefront.orders.confirm', $order->order_number));

        $order->refresh();
        $this->assertTrue($order->deliveryFeePaid());
        $this->assertNotNull($order->delivery_fee_paid_at);
        $this->assertSame(DeliveryFeePaymentStatus::Paid, $payment->fresh()->status);
    }

    public function test_webhook_can_also_confirm_delivery_fee_payment(): void
    {
        config(['services.opay.secret_key' => 'opay_test_secret']);

        $order = $this->placeOrderWithDeliveryFee();

        $payment = DeliveryFeePayment::create([
            'order_id' => $order->id,
            'reference' => 'delfee_webhook_1',
            'amount' => $order->delivery_fee_total,
            'status' => DeliveryFeePaymentStatus::Pending,
        ]);

        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/status' => Http::response([
                'code' => '00000',
                'data' => ['status' => 'SUCCESS'],
            ], 200),
        ]);

        $payloadInner = [
            'amount' => '150000',
            'currency' => 'NGN',
            'reference' => 'delfee_webhook_1',
            'refunded' => false,
            'status' => 'SUCCESS',
            'timestamp' => '2026-07-13T00:00:00Z',
            'token' => 'tok123',
            'transactionId' => 'tok123',
        ];

        $signingString = sprintf(
            '{Amount:"%s",Currency:"%s",Reference:"%s",Refunded:%s,Status:"%s",Timestamp:"%s",Token:"%s",TransactionID:"%s"}',
            $payloadInner['amount'], $payloadInner['currency'], $payloadInner['reference'],
            'f', $payloadInner['status'], $payloadInner['timestamp'], $payloadInner['token'], $payloadInner['transactionId'],
        );

        $body = json_encode([
            'payload' => $payloadInner,
            'sha512' => hash_hmac('sha3-512', $signingString, 'opay_test_secret'),
            'type' => 'transaction-status',
        ]);

        $response = $this->call('POST', route('webhooks.opay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertOk();

        $this->assertSame(DeliveryFeePaymentStatus::Paid, $payment->fresh()->status);
        $this->assertTrue($order->fresh()->deliveryFeePaid());
    }

    public function test_paying_delivery_fee_via_paystack_unlocks_order_confirmation(): void
    {
        $order = $this->placeOrderWithDeliveryFee();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/delfee1', 'reference' => 'delfee_x'],
            ], 200),
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success'],
            ], 200),
        ]);

        Livewire::actingAs($order->user)
            ->test(OtpVerify::class, ['order' => $order])
            ->set('selectedGateway', 'paystack')
            ->call('payDeliveryFee')
            ->assertRedirect('https://checkout.paystack.com/delfee1');

        $payment = DeliveryFeePayment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(\App\Enums\PaymentGateway::Paystack, $payment->gateway);

        $this->actingAs($order->user)
            ->get(route('storefront.orders.delivery-fee.callback', ['order' => $order->order_number, 'reference' => $payment->reference]))
            ->assertRedirect(route('storefront.orders.confirm', $order->order_number));

        $order->refresh();
        $this->assertTrue($order->deliveryFeePaid());
        $this->assertSame(DeliveryFeePaymentStatus::Paid, $payment->fresh()->status);
    }

    public function test_paying_delivery_fee_via_monnify_unlocks_order_confirmation(): void
    {
        \Illuminate\Support\Facades\Cache::forget('monnify_access_token');
        config(['services.monnify.api_key' => 'MK_TEST_KEY', 'services.monnify.secret_key' => 'monnify_test_secret']);

        $order = $this->placeOrderWithDeliveryFee();

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['accessToken' => 'fake-token'],
            ], 200),
            'sandbox.monnify.com/api/v1/merchant/transactions/init-transaction' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['checkoutUrl' => 'https://sandbox.monnify.com/checkout/delfee1', 'paymentReference' => 'delfee_x'],
            ], 200),
            'sandbox.monnify.com/api/v2/transactions/*' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['paymentStatus' => 'PAID'],
            ], 200),
        ]);

        Livewire::actingAs($order->user)
            ->test(OtpVerify::class, ['order' => $order])
            ->set('selectedGateway', 'monnify')
            ->call('payDeliveryFee')
            ->assertRedirect('https://sandbox.monnify.com/checkout/delfee1');

        $payment = DeliveryFeePayment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(\App\Enums\PaymentGateway::Monnify, $payment->gateway);

        $this->actingAs($order->user)
            ->get(route('storefront.orders.delivery-fee.callback', ['order' => $order->order_number, 'reference' => $payment->reference]))
            ->assertRedirect(route('storefront.orders.confirm', $order->order_number));

        $order->refresh();
        $this->assertTrue($order->deliveryFeePaid());
        $this->assertSame(DeliveryFeePaymentStatus::Paid, $payment->fresh()->status);
    }

    public function test_order_with_no_delivery_fee_skips_the_payment_step_entirely(): void
    {
        $this->seed(NigeriaGeographySeeder::class);
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Pickup Only Shop',
            'slug' => 'pickup-only-shop',
            'business_phone' => '+2348012340001',
            'business_address' => '2 Test Street',
            'status' => VendorStatus::Approved,
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Pickup Phone',
            'slug' => 'pickup-phone',
            'base_price' => 5000000,
            'stock' => 10,
            'status' => 'published',
        ]);

        $customer = User::factory()->create(['phone' => '+2348099998877']);
        $cart = \App\Models\Cart::create(['user_id' => $customer->id]);
        \App\Models\CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        Bus::fake([SendOtpSms::class]);

        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();

        Livewire::actingAs($customer)
            ->test(Checkout::class)
            ->set('useNewAddress', true)
            ->set('stateId', $lagos->id)
            ->set('lgaId', $ikeja->id)
            ->set('area', 'Allen Avenue')
            ->set('streetAddress', '10 Test Close')
            ->set('phone', '+2348099998877')
            ->set("fulfillmentMethods.{$vendor->id}", 'pickup')
            ->call('placeOrder');

        $order = Order::firstOrFail();
        $this->assertSame(0, $order->delivery_fee_total);
        $this->assertTrue($order->deliveryFeePaid());

        $code = null;
        Bus::assertDispatched(SendOtpSms::class, function (SendOtpSms $job) use (&$code) {
            $code = $job->code;

            return true;
        });

        Livewire::actingAs($customer)
            ->test(OtpVerify::class, ['order' => $order])
            ->set('code', $code)
            ->call('verify')
            ->assertRedirect(route('storefront.orders.show', $order->order_number));

        $this->assertSame(ConfirmationStatus::Confirmed, $order->fresh()->confirmation_status);
    }
}
