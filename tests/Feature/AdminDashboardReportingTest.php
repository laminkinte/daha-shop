<?php

namespace Tests\Feature;

use App\Enums\ConfirmationStatus;
use App\Enums\OrderStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Livewire\Admin\Dashboard;
use App\Models\Address;
use App\Models\Order;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardReportingTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompletedOrder(int $codCollected, \DateTimeInterface $createdAt): Order
    {
        if (State::count() === 0) {
            $this->seed(NigeriaGeographySeeder::class);
        }

        $state = State::first();
        $lga = $state->lgas()->first();
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
                'phone' => '+2348000000000',
            ])->id,
            'status' => OrderStatus::Completed,
            'confirmation_status' => ConfirmationStatus::Confirmed,
            'items_subtotal' => $codCollected,
            'delivery_fee_total' => 0,
            'cod_amount_expected' => $codCollected,
            'cod_amount_collected' => $codCollected,
        ]);

        $order->forceFill(['created_at' => $createdAt])->save();

        return $order;
    }

    private function makeVendorWithDeliveredRevenue(string $businessName, int $revenue, \DateTimeInterface $deliveredAt): Vendor
    {
        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => $businessName,
            'slug' => \Illuminate\Support\Str::slug($businessName).'-'.uniqid(),
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);

        $order = $this->makeCompletedOrder($revenue, $deliveredAt);

        VendorOrder::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'status' => VendorOrderStatus::Delivered,
            'items_subtotal' => $revenue,
            'delivery_fee' => 0,
            'delivered_at' => $deliveredAt,
        ]);

        return $vendor;
    }

    public function test_date_range_filters_gmv_correctly(): void
    {
        $this->makeCompletedOrder(100000, now()->subDays(60));
        $this->makeCompletedOrder(500000, now());

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Dashboard::class)
            ->set('range', '30d')
            ->assertSee(naira(500000))
            ->assertDontSee(naira(600000));

        Livewire::test(Dashboard::class)
            ->set('range', 'all')
            ->assertSee(naira(600000));
    }

    public function test_top_vendors_ranking_excludes_revenue_outside_the_range(): void
    {
        $this->makeVendorWithDeliveredRevenue('In Range Shop', 200000, now());
        $this->makeVendorWithDeliveredRevenue('Out Of Range Shop', 999999999, now()->subDays(60));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Dashboard::class)
            ->set('range', '30d')
            ->assertSee('In Range Shop')
            ->assertSee(naira(200000))
            // "Out Of Range Shop" still appears in the ranked list (zero
            // revenue for the period), but its huge out-of-range delivery
            // must NOT be counted toward its shown revenue.
            ->assertDontSee(naira(999999999));
    }
}
