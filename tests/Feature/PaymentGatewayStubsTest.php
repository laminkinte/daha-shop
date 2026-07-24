<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway;
use App\Enums\VendorStatus;
use App\Livewire\Vendor\Subscription;
use App\Models\User;
use App\Models\Vendor;
use App\Services\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PaymentGatewayStubsTest extends TestCase
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

    public function test_selecting_palmpay_in_the_subscription_picker_fails_gracefully_not_a_crash(): void
    {
        $vendor = $this->makeVendor();

        Livewire::actingAs($vendor->user)
            ->test(Subscription::class)
            ->set('selectedGateway', 'palmpay')
            ->call('subscribe')
            ->assertHasNoErrors()
            ->assertSet('error', 'We could not start the payment right now. Please try again shortly.')
            ->assertNoRedirect();
    }

    public function test_selecting_kuda_in_the_subscription_picker_fails_gracefully_not_a_crash(): void
    {
        $vendor = $this->makeVendor();

        Livewire::actingAs($vendor->user)
            ->test(Subscription::class)
            ->set('selectedGateway', 'kuda')
            ->call('subscribe')
            ->assertSet('error', 'We could not start the payment right now. Please try again shortly.')
            ->assertNoRedirect();
    }

    public function test_palmpay_client_throws_until_implemented(): void
    {
        $this->expectException(RuntimeException::class);

        app(PaymentGatewayManager::class)
            ->client(PaymentGateway::PalmPay)
            ->initialize('ref', 100000, 'https://example.test');
    }

    public function test_kuda_client_throws_until_implemented(): void
    {
        $this->expectException(RuntimeException::class);

        app(PaymentGatewayManager::class)
            ->client(PaymentGateway::Kuda)
            ->initialize('ref', 100000, 'https://example.test');
    }

    public function test_monnify_and_opay_and_paystack_are_still_resolvable(): void
    {
        $manager = app(PaymentGatewayManager::class);

        $this->assertInstanceOf(\App\Services\MonnifyClient::class, $manager->client(PaymentGateway::Monnify));
        $this->assertInstanceOf(\App\Services\OpayClient::class, $manager->client(PaymentGateway::Opay));
        $this->assertInstanceOf(\App\Services\PaystackClient::class, $manager->client(PaymentGateway::Paystack));
    }
}
