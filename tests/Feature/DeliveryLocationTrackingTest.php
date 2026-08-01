<?php

namespace Tests\Feature;

use App\Enums\AgentAvailability;
use App\Enums\FulfillmentMethod;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Livewire\Agent\DeliveryDetail;
use App\Livewire\Storefront\OrderTracking;
use App\Models\Address;
use App\Models\DeliveryAgent;
use App\Models\Order;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryLocationTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAssignedVendorOrder(VendorOrderStatus $status = VendorOrderStatus::OutForDelivery): VendorOrder
    {
        if (State::count() === 0) {
            $this->seed(NigeriaGeographySeeder::class);
        }
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
            'status' => $status,
            'items_subtotal' => 500000,
            'delivery_fee' => 0,
        ]);
    }

    public function test_updating_location_persists_onto_the_agent_while_out_for_delivery(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->call('updateLocation', 6.5244, 3.3792);

        $agent = $vendorOrder->deliveryAgent->fresh();
        $this->assertEqualsWithDelta(6.5244, (float) $agent->current_lat, 0.0001);
        $this->assertEqualsWithDelta(3.3792, (float) $agent->current_lng, 0.0001);
        $this->assertNotNull($agent->location_updated_at);
    }

    public function test_updating_location_is_a_no_op_when_not_out_for_delivery(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder(VendorOrderStatus::AssignedToAgent);

        Livewire::actingAs($vendorOrder->deliveryAgent->user)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->call('updateLocation', 6.5244, 3.3792)
            ->assertHasNoErrors();

        $agent = $vendorOrder->deliveryAgent->fresh();
        $this->assertNull($agent->current_lat);
        $this->assertNull($agent->current_lng);
        $this->assertNull($agent->location_updated_at);
    }

    public function test_tracking_section_shows_for_an_agent_assigned_out_for_delivery_order(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->assertSee('Live agent location');
    }

    public function test_tracking_section_is_absent_for_pickup_orders(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder(VendorOrderStatus::ReadyForPickup);
        $vendorOrder->update(['fulfillment_method' => FulfillmentMethod::Pickup, 'delivery_agent_id' => null]);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order->fresh()])
            ->assertDontSee('Live agent location');
    }

    public function test_tracking_section_is_absent_for_vendor_self_delivery(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();
        $vendorOrder->update(['delivery_agent_id' => null]);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order->fresh()])
            ->assertDontSee('Live agent location');
    }

    public function test_tracking_section_is_absent_for_other_statuses(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder(VendorOrderStatus::AssignedToAgent);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->assertDontSee('Live agent location');
    }

    public function test_refresh_agent_location_dispatches_the_current_coordinates(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();
        $vendorOrder->deliveryAgent->update([
            'current_lat' => 6.5244,
            'current_lng' => 3.3792,
            'location_updated_at' => now(),
        ]);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->call('refreshAgentLocation', $vendorOrder->id)
            ->assertDispatched('agent-location-updated.'.$vendorOrder->id);
    }

    public function test_refresh_agent_location_does_nothing_for_a_vendor_order_not_on_this_order(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();
        $otherVendorOrder = $this->makeAssignedVendorOrder();

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->call('refreshAgentLocation', $otherVendorOrder->id)
            ->assertNotDispatched('agent-location-updated.'.$otherVendorOrder->id)
            ->assertHasNoErrors();
    }
}
