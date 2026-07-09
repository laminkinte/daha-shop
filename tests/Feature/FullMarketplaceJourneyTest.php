<?php

namespace Tests\Feature;

use App\Enums\AgentAvailability;
use App\Enums\ConfirmationStatus;
use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Jobs\SendOtpSms;
use App\Livewire\Admin\DispatchBoard;
use App\Livewire\Admin\ReconciliationDashboard;
use App\Livewire\Agent\AssignedDeliveries;
use App\Livewire\Agent\DeliveryDetail;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\OtpVerify;
use App\Livewire\Vendor\OrderManager as VendorOrderManager;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DeliveryAgent;
use App\Models\DeliveryFee;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayout;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Drives every role's actual Livewire component (not just the service layer)
 * through one real order, end to end — the closest thing to a browser
 * click-through available in this headless environment.
 */
class FullMarketplaceJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_journey_across_all_four_roles(): void
    {
        $this->seed(NigeriaGeographySeeder::class);

        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Journey Electronics',
            'slug' => 'journey-electronics',
            'business_phone' => '+2348055500001',
            'business_address' => '1 Journey Street',
            'status' => VendorStatus::Approved,
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Journey Phone',
            'slug' => 'journey-phone',
            'base_price' => 3000000,
            'stock' => 10,
            'status' => 'published',
        ]);

        $zone = DeliveryZone::create(['name' => 'Ikeja Zone', 'state_id' => $lagos->id, 'lga_id' => $ikeja->id]);
        DeliveryFee::create(['delivery_zone_id' => $zone->id, 'vendor_id' => null, 'fee' => 100000]);

        $agentUser = User::factory()->agent()->create();
        $agent = DeliveryAgent::create([
            'user_id' => $agentUser->id,
            'state_id' => $lagos->id,
            'lga_id' => $ikeja->id,
            'availability' => AgentAvailability::Available,
        ]);

        $adminUser = User::factory()->admin()->create();

        $customer = User::factory()->create(['phone' => '+2348055500099']);
        $cart = Cart::create(['user_id' => $customer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        // 1. Customer checks out through the real Checkout Livewire component.
        Bus::fake([SendOtpSms::class]);

        Livewire::actingAs($customer)
            ->test(Checkout::class)
            ->set('useNewAddress', true)
            ->set('stateId', $lagos->id)
            ->set('lgaId', $ikeja->id)
            ->set('area', 'Allen Avenue')
            ->set('streetAddress', '1 Journey Close')
            ->set('phone', '+2348055500099')
            ->call('placeOrder')
            ->assertRedirect();

        $order = Order::firstOrFail();

        $code = null;
        Bus::assertDispatched(SendOtpSms::class, function (SendOtpSms $job) use (&$code) {
            $code = $job->code;

            return true;
        });

        // 2. Customer confirms via the real OtpVerify component.
        Livewire::actingAs($customer)
            ->test(OtpVerify::class, ['order' => $order])
            ->set('code', $code)
            ->call('verify')
            ->assertRedirect(route('storefront.orders.show', $order->order_number));

        $this->assertSame(ConfirmationStatus::Confirmed, $order->fresh()->confirmation_status);

        $vendorOrder = $order->vendorOrders()->firstOrFail();

        // 3. Vendor accepts and packs via the real OrderManager component.
        Livewire::actingAs($vendorUser)
            ->test(VendorOrderManager::class)
            ->assertSee($order->order_number)
            ->call('accept', $vendorOrder->id)
            ->call('pack', $vendorOrder->id);

        $this->assertSame(VendorOrderStatus::Packed, $vendorOrder->fresh()->status);

        // 4. Admin assigns an agent via the real DispatchBoard component.
        Livewire::actingAs($adminUser)
            ->test(DispatchBoard::class)
            ->assertSee($order->order_number)
            ->set("selectedAgent.{$vendorOrder->id}", $agent->id)
            ->call('assign', $vendorOrder->id);

        $vendorOrder->refresh();
        $this->assertSame(VendorOrderStatus::AssignedToAgent, $vendorOrder->status);
        $this->assertSame($agent->id, $vendorOrder->delivery_agent_id);

        // 5. Agent starts the delivery via the real AssignedDeliveries component.
        Livewire::actingAs($agentUser)
            ->test(AssignedDeliveries::class)
            ->assertSee($order->order_number)
            ->call('markOutForDelivery', $vendorOrder->id);

        $this->assertSame(VendorOrderStatus::OutForDelivery, $vendorOrder->fresh()->status);

        // 6. Agent completes delivery + collects cash via the real DeliveryDetail component.
        Livewire::actingAs($agentUser)
            ->test(DeliveryDetail::class, ['vendorOrderId' => $vendorOrder->id])
            ->set('cashCollected', number_format($vendorOrder->codTotal() / 100, 2, '.', ''))
            ->set('denominationNotes', 'Paid exact change')
            ->call('markDelivered')
            ->assertRedirect(route('agent.deliveries'));

        $vendorOrder->refresh();
        $order->refresh();
        $this->assertSame(VendorOrderStatus::Delivered, $vendorOrder->status);
        $this->assertSame(OrderStatus::Completed, $order->status);

        $reconciliation = $vendorOrder->cashReconciliation()->firstOrFail();
        $this->assertSame(ReconciliationStatus::Collected, $reconciliation->status);

        // 7. Admin marks the cash remitted via the real ReconciliationDashboard component.
        Livewire::actingAs($adminUser)
            ->test(ReconciliationDashboard::class)
            ->set("remitAmount.{$reconciliation->id}", number_format($reconciliation->amount_collected / 100, 2, '.', ''))
            ->call('remit', $reconciliation->id);

        $this->assertSame(ReconciliationStatus::Remitted, $reconciliation->fresh()->status);

        // 8. Generate + pay the vendor payout via the service layer (no dedicated UI trigger yet).
        $payout = app(\App\Services\PayoutService::class)->generateForVendor($vendor, now()->subDay(), now()->addDay());
        app(\App\Services\PayoutService::class)->markPaid($payout, 'JOURNEY-REF-1');

        $this->assertSame(PayoutStatus::Paid, VendorPayout::findOrFail($payout->id)->status);

        // Vendor sees the paid-out payout in their own history screen.
        Livewire::actingAs($vendorUser)
            ->test(\App\Livewire\Vendor\PayoutHistory::class)
            ->assertSee('JOURNEY-REF-1');
    }
}
