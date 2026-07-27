<?php

namespace Tests\Feature;

use App\Enums\AgentAvailability;
use App\Enums\DeliveryPaymentStatus;
use App\Enums\PaymentGateway;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Livewire\Agent\DeliveryDetail;
use App\Models\Address;
use App\Models\DeliveryAgent;
use App\Models\DeliveryPayment;
use App\Models\Order;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeAssignedVendorOrder(): VendorOrder
    {
        $this->seed(NigeriaGeographySeeder::class);
        $state = State::first();
        $lga = $state->lgas()->first();

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Shop',
            'slug' => 'test-shop-'.uniqid(),
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);

        $agentUser = User::factory()->agent()->create();
        $agent = DeliveryAgent::create([
            'user_id' => $agentUser->id,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'availability' => AgentAvailability::Available,
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
            'items_subtotal' => 500000,
            'delivery_fee_total' => 0,
            'cod_amount_expected' => 500000,
        ]);

        return VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'delivery_agent_id' => $agent->id,
            'status' => VendorOrderStatus::OutForDelivery,
            'items_subtotal' => 500000,
            'delivery_fee' => 0,
        ]);
    }

    public function test_agent_can_start_a_qr_payment(): void
    {
        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/create' => Http::response([
                'code' => '00000',
                'data' => ['reference' => 'x', 'cashierUrl' => 'https://sandboxcashier.opaycheckout.com/checkout/delivery1', 'status' => 'INITIAL'],
            ], 200),
        ]);

        $vendorOrder = $this->makeAssignedVendorOrder();

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->set('paymentMethod', 'qr')
            ->call('startPayment')
            ->assertHasNoErrors();

        $payment = DeliveryPayment::where('vendor_order_id', $vendorOrder->id)->firstOrFail();

        $this->assertSame('qr', $payment->method);
        $this->assertSame(DeliveryPaymentStatus::Pending, $payment->status);
        $this->assertSame('https://sandboxcashier.opaycheckout.com/checkout/delivery1', $payment->cashier_url);
    }

    public function test_manual_payment_requires_a_customer_phone(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->set('paymentMethod', 'manual')
            ->call('startPayment')
            ->assertHasErrors('customerPhone');

        $this->assertNull(DeliveryPayment::where('vendor_order_id', $vendorOrder->id)->first());
    }

    public function test_agent_can_start_a_manual_payment_with_a_phone_number(): void
    {
        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/create' => Http::response([
                'code' => '00000',
                'data' => ['reference' => 'x', 'cashierUrl' => 'https://sandboxcashier.opaycheckout.com/checkout/delivery2', 'status' => 'INITIAL'],
            ], 200),
        ]);

        $vendorOrder = $this->makeAssignedVendorOrder();

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->set('paymentMethod', 'manual')
            ->set('customerPhone', '08012345678')
            ->call('startPayment')
            ->assertHasNoErrors();

        $payment = DeliveryPayment::where('vendor_order_id', $vendorOrder->id)->firstOrFail();

        $this->assertSame('manual', $payment->method);
        $this->assertSame('08012345678', $payment->customer_phone);
    }

    public function test_refreshing_payment_status_completes_delivery_once_paid(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();

        $payment = DeliveryPayment::create([
            'vendor_order_id' => $vendorOrder->id,
            'reference' => 'delivery_'.$vendorOrder->id.'_ref',
            'amount' => 500000,
            'method' => 'qr',
            'status' => DeliveryPaymentStatus::Pending,
            'cashier_url' => 'https://sandboxcashier.opaycheckout.com/checkout/delivery3',
        ]);

        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/status' => Http::response([
                'code' => '00000',
                'data' => ['status' => 'SUCCESS'],
            ], 200),
        ]);

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->call('refreshPaymentStatus')
            ->assertRedirect(route('agent.deliveries'));

        $this->assertSame(DeliveryPaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(VendorOrderStatus::Delivered, $vendorOrder->fresh()->status);
        $this->assertSame(0, $vendorOrder->fresh()->cash_collected);
    }

    public function test_refreshing_payment_status_does_nothing_while_still_pending(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();

        DeliveryPayment::create([
            'vendor_order_id' => $vendorOrder->id,
            'reference' => 'delivery_'.$vendorOrder->id.'_ref2',
            'amount' => 500000,
            'method' => 'qr',
            'status' => DeliveryPaymentStatus::Pending,
        ]);

        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/status' => Http::response([
                'code' => '00000',
                'data' => ['status' => 'INITIAL'],
            ], 200),
        ]);

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->call('refreshPaymentStatus')
            ->assertNoRedirect();

        $this->assertSame(VendorOrderStatus::OutForDelivery, $vendorOrder->fresh()->status);
    }

    /**
     * Regression test: DeliveryPaymentService used to be hardcoded to
     * OpayClient, so without real OPay merchant credentials configured
     * (the normal case for local dev) starting a payment threw and no QR
     * ever rendered. It's now gateway-aware, so agents/vendors can select
     * the local-only Test gateway and complete the flow with no external
     * API calls at all.
     */
    public function test_agent_can_complete_a_delivery_payment_using_the_local_test_gateway(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->set('paymentMethod', 'qr')
            ->set('selectedGateway', 'test')
            ->call('startPayment')
            ->assertHasNoErrors();

        $payment = DeliveryPayment::where('vendor_order_id', $vendorOrder->id)->firstOrFail();
        $this->assertSame(PaymentGateway::Test, $payment->gateway);
        $this->assertNotNull($payment->cashier_url);

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->call('refreshPaymentStatus')
            ->assertRedirect(route('agent.deliveries'));

        $this->assertSame(DeliveryPaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(VendorOrderStatus::Delivered, $vendorOrder->fresh()->status);
    }

    /**
     * Regression test: a payment stuck Pending against a broken gateway
     * (e.g. real OPay with no credentials configured) used to trap the
     * order forever - initialize() short-circuited on any Pending payment
     * regardless of gateway, so there was no way to retry with a working
     * gateway short of manually editing the database. Picking a different
     * gateway now resets the same row in place instead.
     */
    public function test_switching_gateway_resets_a_stuck_pending_payment_in_place(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();

        $stuck = DeliveryPayment::create([
            'vendor_order_id' => $vendorOrder->id,
            'gateway' => PaymentGateway::Opay,
            'reference' => 'delivery_'.$vendorOrder->id.'_stuck',
            'amount' => 500000,
            'method' => 'qr',
            'status' => DeliveryPaymentStatus::Pending,
        ]);

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->set('paymentMethod', 'qr')
            ->set('selectedGateway', 'test')
            ->call('startPayment')
            ->assertHasNoErrors();

        $this->assertSame(1, DeliveryPayment::where('vendor_order_id', $vendorOrder->id)->count());

        $payment = $stuck->fresh();
        $this->assertSame(PaymentGateway::Test, $payment->gateway);
        $this->assertNotSame('delivery_'.$vendorOrder->id.'_stuck', $payment->reference);
        $this->assertNotNull($payment->cashier_url);
    }

    /**
     * Regression test: a gateway API failure during the 5s polling loop
     * used to bubble up as an uncaught RuntimeException and 500 the whole
     * Livewire request - repeating on every poll tick until the underlying
     * gateway config was fixed. It must now fail quietly and leave the
     * payment retryable instead of crashing the page.
     */
    public function test_refreshing_payment_status_does_not_crash_when_the_gateway_call_fails(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();

        DeliveryPayment::create([
            'vendor_order_id' => $vendorOrder->id,
            'gateway' => PaymentGateway::Opay,
            'reference' => 'delivery_'.$vendorOrder->id.'_broken',
            'amount' => 500000,
            'method' => 'qr',
            'status' => DeliveryPaymentStatus::Pending,
        ]);

        Http::fake([
            'sandboxapi.opaycheckout.com/api/v1/international/cashier/status' => Http::response([
                'code' => '02000',
                'message' => 'missing request header [MerchantId]',
            ], 400),
        ]);

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->call('refreshPaymentStatus')
            ->assertNoRedirect();

        $this->assertSame(DeliveryPaymentStatus::Pending, $vendorOrder->fresh()->deliveryPayment->status);
    }
}
