<?php

namespace Tests\Feature;

use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSee($user->phone)
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form')
            ->assertSeeVolt('profile.delete-user-form')
            ->assertDontSeeVolt('profile.update-pin-form')
            ->assertDontSeeVolt('profile.update-vendor-business-form');
    }

    public function test_profile_page_shows_the_pin_form_and_no_email_field_for_phone_pin_accounts(): void
    {
        $user = User::factory()->create(['uses_pin' => true]);

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-pin-form')
            ->assertDontSeeVolt('profile.update-password-form');
    }

    public function test_phone_pin_account_can_update_their_pin(): void
    {
        $user = User::factory()->create(['uses_pin' => true, 'password' => bcrypt('1234')]);

        $this->actingAs($user);

        Volt::test('profile.update-pin-form')
            ->set('current_pin', '1234')
            ->set('pin', '5678')
            ->set('pin_confirmation', '5678')
            ->call('updatePin')
            ->assertHasNoErrors();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('5678', $user->fresh()->password));
    }

    public function test_pin_must_be_exactly_four_digits(): void
    {
        $user = User::factory()->create(['uses_pin' => true, 'password' => bcrypt('1234')]);

        $this->actingAs($user);

        Volt::test('profile.update-pin-form')
            ->set('current_pin', '1234')
            ->set('pin', '12')
            ->set('pin_confirmation', '12')
            ->call('updatePin')
            ->assertHasErrors('pin');
    }

    public function test_profile_page_shows_the_business_form_for_vendors_only(): void
    {
        $vendorUser = User::factory()->vendor()->create();
        Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Shop',
            'slug' => 'test-shop',
            'business_phone' => '+2348000000000',
            'business_address' => 'Address',
            'status' => VendorStatus::Approved,
        ]);

        $this->actingAs($vendorUser)->get('/profile')->assertSeeVolt('profile.update-vendor-business-form');

        $customer = User::factory()->create();
        $this->actingAs($customer)->get('/profile')->assertDontSeeVolt('profile.update-vendor-business-form');
    }

    public function test_vendor_can_update_business_and_bank_details(): void
    {
        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Shop',
            'slug' => 'test-shop',
            'business_phone' => '+2348000000000',
            'business_address' => 'Address',
            'status' => VendorStatus::Approved,
        ]);

        $this->actingAs($vendorUser);

        Volt::test('profile.update-vendor-business-form')
            ->set('business_name', 'Updated Shop Name')
            ->set('business_phone', '+2348011112222')
            ->set('business_address', '2 New Street')
            ->set('bank_name', 'GTBank')
            ->set('bank_account_number', '0123456789')
            ->set('bank_account_name', 'Test Shop Ltd')
            ->call('updateBusinessInfo')
            ->assertHasNoErrors();

        $vendor->refresh();
        $this->assertSame('Updated Shop Name', $vendor->business_name);
        $this->assertSame('GTBank', $vendor->bank_name);
        $this->assertSame('0123456789', $vendor->bank_account_number);
        $this->assertSame('Test Shop Ltd', $vendor->bank_account_name);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh());
    }
}
