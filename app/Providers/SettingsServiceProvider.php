<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * Lets admin-edited business settings override config/markethub.php and
 * config/subscriptions.php values without touching the many existing
 * config('markethub.xxx') / config('subscriptions.xxx') call sites - the
 * config files remain the fallback defaults when no override is saved.
 */
class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Guard against running before migrations exist (fresh install, or
        // artisan commands that boot the app before `migrate` has run).
        if (! Schema::hasTable('settings')) {
            return;
        }

        $overrides = [
            'markethub.max_cod_auto_confirm_amount' => 'max_cod_auto_confirm_amount',
            'markethub.max_delivery_attempts' => 'max_delivery_attempts',
            'subscriptions.plans.monthly.amount' => 'subscription_monthly_amount',
            'subscriptions.plans.annual.amount' => 'subscription_annual_amount',
        ];

        foreach ($overrides as $configKey => $settingKey) {
            $value = Setting::get($settingKey);

            if ($value !== null) {
                config([$configKey => (int) $value]);
            }
        }
    }
}
