<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\VendorStatus;
use App\Livewire\Vendor\ProductManager;
use App\Livewire\Vendor\Subscription;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class VendorSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(): Vendor
    {
        $user = User::factory()->vendor()->create();

        return Vendor::create([
            'user_id' => $user->id,
            'business_name' => 'Test Shop',
            'slug' => 'test-shop-'.$user->id,
            'business_phone' => '+2348000000000',
            'business_address' => 'Address',
            'status' => VendorStatus::Approved,
        ]);
    }

    public function test_subscribing_initializes_a_pending_subscription_and_redirects_to_paystack(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/abc123',
                    'access_code' => 'abc123',
                    'reference' => 'sub_ref_1',
                ],
            ], 200),
        ]);

        $vendor = $this->makeVendor();

        Livewire::actingAs($vendor->user)
            ->test(Subscription::class)
            ->set('selectedPlan', 'monthly')
            ->call('subscribe')
            ->assertRedirect('https://checkout.paystack.com/abc123');

        $subscription = VendorSubscription::where('vendor_id', $vendor->id)->firstOrFail();

        $this->assertSame(SubscriptionStatus::Pending, $subscription->status);
        $this->assertSame(SubscriptionPlan::Monthly, $subscription->plan);
        $this->assertSame(500000, $subscription->amount);

        Http::assertSent(function ($request) use ($subscription) {
            return $request->url() === 'https://api.paystack.co/transaction/initialize'
                && $request['reference'] === $subscription->reference
                && $request['amount'] === 500000;
        });
    }

    public function test_callback_activates_subscription_when_paystack_confirms_success(): void
    {
        $vendor = $this->makeVendor();

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_2',
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'reference' => 'sub_ref_2'],
            ], 200),
        ]);

        $response = $this->actingAs($vendor->user)
            ->get(route('vendor.subscription.callback', ['reference' => 'sub_ref_2']));

        $response->assertRedirect(route('vendor.subscription'));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertNotNull($subscription->expires_at);
        $this->assertTrue($subscription->expires_at->isFuture());
        $this->assertTrue($vendor->fresh()->hasActiveSubscription());
    }

    public function test_callback_marks_subscription_failed_when_paystack_reports_failure(): void
    {
        $vendor = $this->makeVendor();

        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_3',
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'failed', 'reference' => 'sub_ref_3'],
            ], 200),
        ]);

        $this->actingAs($vendor->user)
            ->get(route('vendor.subscription.callback', ['reference' => 'sub_ref_3']));

        $this->assertSame(SubscriptionStatus::Failed, VendorSubscription::where('reference', 'sub_ref_3')->firstOrFail()->status);
        $this->assertFalse($vendor->fresh()->hasActiveSubscription());
    }

    public function test_webhook_activates_subscription_with_valid_signature(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_secret']);

        $vendor = $this->makeVendor();

        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Annual,
            'amount' => 5000000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_4',
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'reference' => 'sub_ref_4'],
            ], 200),
        ]);

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'sub_ref_4']]);
        $signature = hash_hmac('sha512', $payload, 'sk_test_secret');

        $response = $this->call('POST', route('webhooks.paystack'), [], [], [], [
            'HTTP_x-paystack-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $subscription = VendorSubscription::where('reference', 'sub_ref_4')->firstOrFail();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_secret']);

        $vendor = $this->makeVendor();

        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_5',
        ]);

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'sub_ref_5']]);

        $response = $this->call('POST', route('webhooks.paystack'), [], [], [], [
            'HTTP_x-paystack-signature' => 'not-the-right-signature',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(401);

        $this->assertSame(SubscriptionStatus::Pending, VendorSubscription::where('reference', 'sub_ref_5')->firstOrFail()->status);
    }

    public function test_vendor_without_active_subscription_cannot_open_new_product_form(): void
    {
        $vendor = $this->makeVendor();
        Category::create(['name' => 'Phones', 'slug' => 'phones']);

        Livewire::actingAs($vendor->user)
            ->test(ProductManager::class)
            ->call('create')
            ->assertSet('showForm', false)
            ->assertSet('subscriptionRequired', true)
            ->assertSee('active subscription');
    }

    public function test_vendor_with_active_subscription_can_open_new_product_form(): void
    {
        $vendor = $this->makeVendor();
        Category::create(['name' => 'Phones', 'slug' => 'phones']);

        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Active,
            'paid_at' => now(),
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'reference' => 'sub_ref_6',
        ]);

        Livewire::actingAs($vendor->user)
            ->test(ProductManager::class)
            ->call('create')
            ->assertSet('showForm', true)
            ->assertSet('subscriptionRequired', false);
    }

    public function test_renewing_before_expiry_extends_from_current_expiry_not_from_now(): void
    {
        $vendor = $this->makeVendor();

        $existingExpiry = now()->addDays(10);
        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Active,
            'paid_at' => now()->subDays(20),
            'starts_at' => now()->subDays(20),
            'expires_at' => $existingExpiry,
            'reference' => 'sub_ref_7',
        ]);

        $renewal = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_8',
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'reference' => 'sub_ref_8'],
            ], 200),
        ]);

        app(\App\Services\SubscriptionService::class)->verifyAndActivate('sub_ref_8');

        $renewal->refresh();
        $this->assertTrue($renewal->expires_at->isSameDay($existingExpiry->copy()->addMonth()));
    }

    public function test_expire_subscriptions_command_marks_past_due_active_subscriptions_expired(): void
    {
        $vendor = $this->makeVendor();

        $expired = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Active,
            'paid_at' => now()->subMonths(2),
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subDay(),
            'reference' => 'sub_ref_9',
        ]);

        $stillActive = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Active,
            'paid_at' => now(),
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'reference' => 'sub_ref_10',
        ]);

        $this->artisan('app:expire-vendor-subscriptions')->assertExitCode(0);

        $this->assertSame(SubscriptionStatus::Expired, $expired->fresh()->status);
        $this->assertSame(SubscriptionStatus::Active, $stillActive->fresh()->status);
    }

    public function test_subscribing_with_opay_initializes_and_redirects_to_cashier_url(): void
    {
        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/create' => Http::response([
                'code' => '00000',
                'message' => 'SUCCESSFUL',
                'data' => [
                    'reference' => 'sub_ref_opay_1',
                    'orderNo' => '211009140896553163',
                    'cashierUrl' => 'https://sandboxcashier.opaycheckout.com/checkout/abc123',
                    'status' => 'INITIAL',
                ],
            ], 200),
        ]);

        $vendor = $this->makeVendor();

        Livewire::actingAs($vendor->user)
            ->test(Subscription::class)
            ->set('selectedPlan', 'monthly')
            ->set('selectedGateway', 'opay')
            ->call('subscribe')
            ->assertRedirect('https://sandboxcashier.opaycheckout.com/checkout/abc123');

        $subscription = VendorSubscription::where('vendor_id', $vendor->id)->firstOrFail();

        $this->assertSame(PaymentGateway::Opay, $subscription->gateway);
        $this->assertSame(SubscriptionStatus::Pending, $subscription->status);

        Http::assertSent(function ($request) use ($subscription) {
            return $request->url() === 'https://sandboxapi.opaycheckout.com/api/v1/international/cashier/create'
                && $request['reference'] === $subscription->reference
                && $request['amount']['total'] === 500000
                && $request['amount']['currency'] === 'NGN'
                && str_contains($request['returnUrl'], 'reference='.$subscription->reference);
        });
    }

    public function test_opay_callback_activates_subscription_when_status_query_reports_success(): void
    {
        $vendor = $this->makeVendor();

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'gateway' => PaymentGateway::Opay,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_opay_2',
        ]);

        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/status' => Http::response([
                'code' => '00000',
                'message' => 'SUCCESSFUL',
                'data' => ['reference' => 'sub_ref_opay_2', 'status' => 'SUCCESS'],
            ], 200),
        ]);

        $response = $this->actingAs($vendor->user)
            ->get(route('vendor.subscription.callback', ['reference' => 'sub_ref_opay_2']));

        $response->assertRedirect(route('vendor.subscription'));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertTrue($vendor->fresh()->hasActiveSubscription());
    }

    public function test_opay_webhook_activates_subscription_with_valid_sha3_signature(): void
    {
        config(['services.opay.secret_key' => 'opay_test_secret']);

        $vendor = $this->makeVendor();

        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'gateway' => PaymentGateway::Opay,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_opay_3',
        ]);

        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/status' => Http::response([
                'code' => '00000',
                'message' => 'SUCCESSFUL',
                'data' => ['reference' => 'sub_ref_opay_3', 'status' => 'SUCCESS'],
            ], 200),
        ]);

        $payloadInner = [
            'amount' => '500000',
            'currency' => 'NGN',
            'reference' => 'sub_ref_opay_3',
            'refunded' => false,
            'status' => 'SUCCESS',
            'timestamp' => '2026-07-12T11:46:26Z',
            'token' => '211215140485151728',
            'transactionId' => '211215140485151728',
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

        $subscription = VendorSubscription::where('reference', 'sub_ref_opay_3')->firstOrFail();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
    }

    public function test_opay_webhook_rejects_invalid_signature(): void
    {
        config(['services.opay.secret_key' => 'opay_test_secret']);

        $vendor = $this->makeVendor();

        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'gateway' => PaymentGateway::Opay,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_opay_4',
        ]);

        $body = json_encode([
            'payload' => ['amount' => '500000', 'currency' => 'NGN', 'reference' => 'sub_ref_opay_4', 'refunded' => false, 'status' => 'SUCCESS', 'timestamp' => 'now', 'token' => 'x', 'transactionId' => 'x'],
            'sha512' => 'not-the-right-signature',
            'type' => 'transaction-status',
        ]);

        $response = $this->call('POST', route('webhooks.opay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertStatus(401);

        $this->assertSame(SubscriptionStatus::Pending, VendorSubscription::where('reference', 'sub_ref_opay_4')->firstOrFail()->status);
    }

    public function test_subscribing_with_monnify_initializes_and_redirects_to_checkout_url(): void
    {
        \Illuminate\Support\Facades\Cache::forget('monnify_access_token');
        config(['services.monnify.api_key' => 'MK_TEST_KEY', 'services.monnify.secret_key' => 'monnify_test_secret']);

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['accessToken' => 'fake-token'],
            ], 200),
            'sandbox.monnify.com/api/v1/merchant/transactions/init-transaction' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => [
                    'checkoutUrl' => 'https://sandbox.monnify.com/checkout/abc123',
                    'paymentReference' => 'sub_ref_monnify_1',
                ],
            ], 200),
        ]);

        $vendor = $this->makeVendor();

        Livewire::actingAs($vendor->user)
            ->test(Subscription::class)
            ->set('selectedPlan', 'monthly')
            ->set('selectedGateway', 'monnify')
            ->call('subscribe')
            ->assertRedirect('https://sandbox.monnify.com/checkout/abc123');

        $subscription = VendorSubscription::where('vendor_id', $vendor->id)->firstOrFail();

        $this->assertSame(PaymentGateway::Monnify, $subscription->gateway);
        $this->assertSame(SubscriptionStatus::Pending, $subscription->status);

        Http::assertSent(function ($request) use ($subscription) {
            return $request->url() === 'https://sandbox.monnify.com/api/v1/merchant/transactions/init-transaction'
                && $request['paymentReference'] === $subscription->reference
                && (float) $request['amount'] === 5000.0
                && $request['currencyCode'] === 'NGN';
        });
    }

    public function test_monnify_callback_activates_subscription_when_status_query_reports_paid(): void
    {
        \Illuminate\Support\Facades\Cache::forget('monnify_access_token');
        config(['services.monnify.api_key' => 'MK_TEST_KEY', 'services.monnify.secret_key' => 'monnify_test_secret']);

        $vendor = $this->makeVendor();

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'gateway' => PaymentGateway::Monnify,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_monnify_2',
        ]);

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['accessToken' => 'fake-token'],
            ], 200),
            'sandbox.monnify.com/api/v2/transactions/*' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['paymentReference' => 'sub_ref_monnify_2', 'paymentStatus' => 'PAID'],
            ], 200),
        ]);

        $response = $this->actingAs($vendor->user)
            ->get(route('vendor.subscription.callback', ['reference' => 'sub_ref_monnify_2']));

        $response->assertRedirect(route('vendor.subscription'));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertTrue($vendor->fresh()->hasActiveSubscription());
    }

    public function test_monnify_webhook_activates_subscription_with_valid_signature(): void
    {
        \Illuminate\Support\Facades\Cache::forget('monnify_access_token');
        config(['services.monnify.api_key' => 'MK_TEST_KEY', 'services.monnify.secret_key' => 'monnify_test_secret']);

        $vendor = $this->makeVendor();

        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'gateway' => PaymentGateway::Monnify,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_monnify_3',
        ]);

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['accessToken' => 'fake-token'],
            ], 200),
            'sandbox.monnify.com/api/v2/transactions/*' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['paymentReference' => 'sub_ref_monnify_3', 'paymentStatus' => 'PAID'],
            ], 200),
        ]);

        $eventData = [
            'transactionReference' => 'MNFY|20260712|000001',
            'paymentReference' => 'sub_ref_monnify_3',
            'amountPaid' => '5000.00',
            'paidOn' => '2026-07-12 11:46:26.0',
        ];

        $signingString = implode('|', [
            'MK_TEST_KEY',
            $eventData['transactionReference'],
            $eventData['paymentReference'],
            $eventData['amountPaid'],
            $eventData['paidOn'],
            'monnify_test_secret',
        ]);

        $body = json_encode(['eventType' => 'SUCCESSFUL_TRANSACTION', 'eventData' => $eventData]);
        $signature = hash_hmac('sha512', $signingString, 'monnify_test_secret');

        $response = $this->call('POST', route('webhooks.monnify'), [], [], [], [
            'HTTP_monnify-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertOk();

        $subscription = VendorSubscription::where('reference', 'sub_ref_monnify_3')->firstOrFail();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
    }

    public function test_monnify_webhook_rejects_invalid_signature(): void
    {
        config(['services.monnify.api_key' => 'MK_TEST_KEY', 'services.monnify.secret_key' => 'monnify_test_secret']);

        $vendor = $this->makeVendor();

        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'gateway' => PaymentGateway::Monnify,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'reference' => 'sub_ref_monnify_4',
        ]);

        $body = json_encode(['eventType' => 'SUCCESSFUL_TRANSACTION', 'eventData' => ['paymentReference' => 'sub_ref_monnify_4']]);

        $response = $this->call('POST', route('webhooks.monnify'), [], [], [], [
            'HTTP_monnify-signature' => 'not-the-right-signature',
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertStatus(401);

        $this->assertSame(SubscriptionStatus::Pending, VendorSubscription::where('reference', 'sub_ref_monnify_4')->firstOrFail()->status);
    }
}
