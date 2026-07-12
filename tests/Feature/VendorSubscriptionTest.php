<?php

namespace Tests\Feature;

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
                && $request['reference'] === $subscription->paystack_reference
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
            'paystack_reference' => 'sub_ref_2',
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
            'paystack_reference' => 'sub_ref_3',
        ]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'failed', 'reference' => 'sub_ref_3'],
            ], 200),
        ]);

        $this->actingAs($vendor->user)
            ->get(route('vendor.subscription.callback', ['reference' => 'sub_ref_3']));

        $this->assertSame(SubscriptionStatus::Failed, VendorSubscription::where('paystack_reference', 'sub_ref_3')->firstOrFail()->status);
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
            'paystack_reference' => 'sub_ref_4',
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

        $subscription = VendorSubscription::where('paystack_reference', 'sub_ref_4')->firstOrFail();
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
            'paystack_reference' => 'sub_ref_5',
        ]);

        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'sub_ref_5']]);

        $response = $this->call('POST', route('webhooks.paystack'), [], [], [], [
            'HTTP_x-paystack-signature' => 'not-the-right-signature',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(401);

        $this->assertSame(SubscriptionStatus::Pending, VendorSubscription::where('paystack_reference', 'sub_ref_5')->firstOrFail()->status);
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
            'paystack_reference' => 'sub_ref_6',
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
            'paystack_reference' => 'sub_ref_7',
        ]);

        $renewal = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Pending,
            'paystack_reference' => 'sub_ref_8',
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
            'paystack_reference' => 'sub_ref_9',
        ]);

        $stillActive = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => SubscriptionPlan::Monthly,
            'amount' => 500000,
            'status' => SubscriptionStatus::Active,
            'paid_at' => now(),
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'paystack_reference' => 'sub_ref_10',
        ]);

        $this->artisan('app:expire-vendor-subscriptions')->assertExitCode(0);

        $this->assertSame(SubscriptionStatus::Expired, $expired->fresh()->status);
        $this->assertSame(SubscriptionStatus::Active, $stillActive->fresh()->status);
    }
}
