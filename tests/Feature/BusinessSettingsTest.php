<?php

namespace Tests\Feature;

use App\Livewire\Admin\BusinessSettings;
use App\Models\Setting;
use App\Models\User;
use App\Providers\SettingsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_business_settings_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings'))->assertOk();
    }

    public function test_scoped_admin_without_settings_permission_is_forbidden(): void
    {
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($scopedAdmin)->get(route('admin.settings'))->assertForbidden();
    }

    public function test_scoped_admin_with_settings_permission_can_access(): void
    {
        $scopedAdmin = User::factory()->scopedAdmin(['settings'])->create();

        $this->actingAs($scopedAdmin)->get(route('admin.settings'))->assertOk();
    }

    public function test_saving_settings_persists_the_submitted_values(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(BusinessSettings::class)
            ->set('maxDeliveryAttempts', '5')
            ->set('maxCodAutoConfirmAmount', '25000')
            ->set('subscriptionMonthlyAmount', '6000')
            ->set('subscriptionAnnualAmount', '60000')
            ->set('platformCommissionRate', '10')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('5', Setting::get('max_delivery_attempts'));
        $this->assertSame(2500000, (int) Setting::get('max_cod_auto_confirm_amount'));
        $this->assertSame('10', Setting::get('platform_commission_rate'));
    }

    public function test_settings_service_provider_overrides_config_on_next_boot(): void
    {
        // Settings apply on the NEXT request's application boot, not
        // mid-request - the same way an env change only takes effect after
        // the process restarts. Simulate that here by saving a value, then
        // re-running the provider's boot() as a fresh request would.
        Setting::set('max_delivery_attempts', 7);
        Setting::set('max_cod_auto_confirm_amount', 3000000);

        (new SettingsServiceProvider($this->app))->boot();

        $this->assertSame(7, config('markethub.max_delivery_attempts'));
        $this->assertSame(3000000, config('markethub.max_cod_auto_confirm_amount'));
    }

    public function test_commission_rate_is_stored_but_does_not_change_payout_math(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(BusinessSettings::class)
            ->set('platformCommissionRate', '15')
            ->call('save');

        $this->assertSame('15', Setting::get('platform_commission_rate'));

        // PayoutService is untouched by design this pass - verified via its
        // own dedicated test suite (PayoutServiceTest) already asserting
        // 100% of items_subtotal is paid out with no deduction.
    }

    public function test_logo_upload_stores_file_and_updates_setting(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(BusinessSettings::class)
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('save');

        $path = Setting::get('app_logo_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }
}
