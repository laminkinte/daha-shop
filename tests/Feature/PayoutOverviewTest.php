<?php

namespace Tests\Feature;

use App\Enums\PayoutStatus;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(string $name): Vendor
    {
        $vendorUser = User::factory()->vendor()->create();

        return Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name).'-'.uniqid(),
            'business_phone' => '+2348012340000',
            'business_address' => '1 Test Street',
            'status' => VendorStatus::Approved,
        ]);
    }

    public function test_admin_can_view_payouts_list(): void
    {
        $vendor = $this->makeVendor('Paid Shop');
        VendorPayout::create([
            'vendor_id' => $vendor->id,
            'period_start' => now()->subDays(30),
            'period_end' => now(),
            'total_amount' => 500000,
            'status' => PayoutStatus::Paid,
        ]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.payouts'))->assertOk()->assertSee('Paid Shop');
    }

    public function test_payouts_list_can_be_filtered_by_status(): void
    {
        $paidVendor = $this->makeVendor('Paid Shop');
        $pendingVendor = $this->makeVendor('Pending Shop');

        VendorPayout::create([
            'vendor_id' => $paidVendor->id,
            'period_start' => now()->subDays(30),
            'period_end' => now(),
            'total_amount' => 500000,
            'status' => PayoutStatus::Paid,
        ]);

        VendorPayout::create([
            'vendor_id' => $pendingVendor->id,
            'period_start' => now()->subDays(30),
            'period_end' => now(),
            'total_amount' => 300000,
            'status' => PayoutStatus::Pending,
        ]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Livewire\Admin\PayoutOverview::class)
            ->set('filter', 'paid')
            ->assertSee('Paid Shop')
            ->assertDontSee('Pending Shop');
    }

    public function test_scoped_admin_without_payouts_permission_is_forbidden(): void
    {
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($scopedAdmin)->get(route('admin.payouts'))->assertForbidden();
    }
}
