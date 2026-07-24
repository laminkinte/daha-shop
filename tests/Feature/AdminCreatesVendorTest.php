<?php

namespace Tests\Feature;

use App\Enums\VendorStatus;
use App\Livewire\Admin\VendorApprovals;
use App\Livewire\Vendor\ProductManager;
use App\Mail\VendorAccountCreatedMail;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCreatesVendorTest extends TestCase
{
    use RefreshDatabase;

    private function fillForm()
    {
        return Livewire::test(VendorApprovals::class)
            ->set('name', 'Ada Owner')
            ->set('email', 'ada-shop@example.com')
            ->set('phone', '+2348099998888')
            ->set('businessName', 'Ada Fashion House')
            ->set('businessPhone', '+2348099998888')
            ->set('businessAddress', '12 Market Road');
    }

    public function test_admin_can_create_an_auto_approved_vendor(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->fillForm()->call('create')->assertHasNoErrors();

        $user = User::where('email', 'ada-shop@example.com')->firstOrFail();
        $vendor = Vendor::where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($user->isVendor());
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->phone_verified_at);

        $this->assertSame(VendorStatus::Approved, $vendor->status);
        $this->assertNotNull($vendor->approved_at);
        $this->assertSame($admin->id, $vendor->reviewed_by);
        $this->assertSame('Ada Fashion House', $vendor->business_name);

        Mail::assertQueued(VendorAccountCreatedMail::class, fn ($mail) => $mail->hasTo('ada-shop@example.com'));
    }

    public function test_newly_created_vendor_can_log_in_and_reach_their_dashboard_immediately(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $this->fillForm()->call('create');

        $vendorUser = User::where('email', 'ada-shop@example.com')->firstOrFail();

        $this->actingAs($vendorUser)->get(route('vendor.dashboard'))->assertOk();
    }

    public function test_newly_created_vendor_still_needs_a_subscription_to_list_products(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $this->fillForm()->call('create');

        $vendorUser = User::where('email', 'ada-shop@example.com')->firstOrFail();
        $this->actingAs($vendorUser);

        Livewire::test(ProductManager::class)
            ->call('create')
            ->assertSet('showForm', false)
            ->assertSet('subscriptionRequired', true)
            ->assertSee('active subscription');
    }

    public function test_duplicate_email_shows_a_validation_error(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $existing = User::factory()->create();
        $this->actingAs($admin);

        $this->fillForm()->set('email', $existing->email)->call('create')->assertHasErrors('email');
    }

    public function test_duplicate_phone_shows_a_validation_error(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $existing = User::factory()->create();
        $this->actingAs($admin);

        $this->fillForm()->set('phone', $existing->phone)->call('create')->assertHasErrors('phone');
    }

    public function test_scoped_admin_without_vendors_permission_cannot_reach_the_page(): void
    {
        $scopedAdmin = User::factory()->scopedAdmin(['products'])->create();

        $this->actingAs($scopedAdmin)->get(route('admin.vendors'))->assertForbidden();
    }
}
