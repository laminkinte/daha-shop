<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_dashboard_is_accessible_to_vendors_only(): void
    {
        $vendorUser = User::factory()->vendor()->create();
        \App\Models\Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Vendor Biz',
            'slug' => 'vendor-biz',
            'business_phone' => '+2348000000001',
            'business_address' => 'Somewhere',
            'status' => \App\Enums\VendorStatus::Approved,
        ]);

        $this->actingAs($vendorUser)->get(route('vendor.dashboard'))->assertOk();

        $customer = User::factory()->create();
        $this->actingAs($customer)->get(route('vendor.dashboard'))->assertForbidden();
    }

    public function test_admin_dashboard_is_accessible_to_admins_only(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $customer = User::factory()->create();
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_agent_app_is_accessible_to_agents_only(): void
    {
        $agentUser = User::factory()->agent()->create();
        \App\Models\DeliveryAgent::create(['user_id' => $agentUser->id]);

        $this->actingAs($agentUser)->get(route('agent.deliveries'))->assertOk();

        $customer = User::factory()->create();
        $this->actingAs($customer)->get(route('agent.deliveries'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login_for_protected_routes(): void
    {
        $this->get(route('vendor.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('agent.deliveries'))->assertRedirect(route('login'));
        $this->get(route('storefront.checkout'))->assertRedirect(route('login'));
    }
}
