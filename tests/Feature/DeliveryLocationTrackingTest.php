<?php

namespace Tests\Feature;

use App\Enums\AgentAvailability;
use App\Enums\FulfillmentMethod;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Livewire\Agent\DeliveryDetail;
use App\Livewire\Storefront\OrderTracking;
use App\Livewire\Vendor\OrderManager;
use App\Models\Address;
use App\Models\DeliveryAgent;
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
            ->assertSee("Waiting for your delivery agent's location", false);
    }

    public function test_tracking_section_shows_the_live_map_once_the_agent_has_a_location(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 200)]);

        $vendorOrder = $this->makeAssignedVendorOrder();
        $vendorOrder->deliveryAgent->update([
            'current_lat' => 6.5244,
            'current_lng' => 3.3792,
            'location_updated_at' => now(),
        ]);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order->fresh()])
            ->assertSee('Out for delivery')
            ->assertSee('See all orders')
            ->assertDontSee("Waiting for your delivery agent's location", false);
    }

    public function test_tracking_section_is_absent_for_pickup_orders(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder(VendorOrderStatus::ReadyForPickup);
        $vendorOrder->update(['fulfillment_method' => FulfillmentMethod::Pickup, 'delivery_agent_id' => null]);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order->fresh()])
            ->assertDontSee("Waiting for your delivery agent's location", false);
    }

    public function test_tracking_section_shows_the_waiting_placeholder_for_vendor_self_delivery(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();
        $vendorOrder->update(['delivery_agent_id' => null]);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order->fresh()])
            ->assertSee("Waiting for your delivery agent's location", false);
    }

    public function test_tracking_section_shows_the_live_map_for_self_delivery_once_the_vendor_has_a_location(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 200)]);

        $vendorOrder = $this->makeAssignedVendorOrder();
        $vendorOrder->update(['delivery_agent_id' => null]);
        $vendorOrder->vendor->update([
            'current_lat' => 6.5244,
            'current_lng' => 3.3792,
            'location_updated_at' => now(),
        ]);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order->fresh()])
            ->assertSee('Out for delivery')
            ->assertDontSee("Waiting for your delivery agent's location", false);
    }

    public function test_vendor_update_location_persists_while_self_delivering(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();
        $vendorOrder->update(['delivery_agent_id' => null]);

        Livewire::actingAs($vendorOrder->vendor->user)
            ->test(OrderManager::class)
            ->call('updateLocation', 6.5244, 3.3792);

        $vendor = $vendorOrder->vendor->fresh();
        $this->assertEqualsWithDelta(6.5244, (float) $vendor->current_lat, 0.0001);
        $this->assertEqualsWithDelta(3.3792, (float) $vendor->current_lng, 0.0001);
        $this->assertNotNull($vendor->location_updated_at);
    }

    public function test_vendor_update_location_is_a_no_op_when_not_self_delivering(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder();

        Livewire::actingAs($vendorOrder->vendor->user)
            ->test(OrderManager::class)
            ->call('updateLocation', 6.5244, 3.3792)
            ->assertHasNoErrors();

        $vendor = $vendorOrder->vendor->fresh();
        $this->assertNull($vendor->current_lat);
        $this->assertNull($vendor->current_lng);
    }

    public function test_tracking_section_is_absent_for_other_statuses(): void
    {
        $vendorOrder = $this->makeAssignedVendorOrder(VendorOrderStatus::AssignedToAgent);

        Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order])
            ->assertDontSee("Waiting for your delivery agent's location", false);
    }

    public function test_refresh_agent_location_dispatches_the_current_coordinates(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 200)]);

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

    public function test_tracking_map_plots_the_geocoded_destination_alongside_the_live_position(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '6.4500', 'lon' => '3.4000'],
            ], 200),
        ]);

        $vendorOrder = $this->makeAssignedVendorOrder();
        $vendorOrder->deliveryAgent->update([
            'current_lat' => 6.5244,
            'current_lng' => 3.3792,
            'location_updated_at' => now(),
        ]);

        $html = Livewire::actingAs($vendorOrder->order->user)
            ->test(OrderTracking::class, ['order' => $vendorOrder->order->fresh()])
            ->html();

        $this->assertStringContainsString('destLat: 6.45', $html);
        $this->assertStringContainsString('destLng: 3.4', $html);

        $address = $vendorOrder->order->address->fresh();
        $this->assertEqualsWithDelta(6.45, (float) $address->lat, 0.0001);
        $this->assertEqualsWithDelta(3.4, (float) $address->lng, 0.0001);
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
