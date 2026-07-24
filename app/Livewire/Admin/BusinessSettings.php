<?php

namespace App\Livewire\Admin;

use App\Models\AdminActionLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
class BusinessSettings extends Component
{
    use WithFileUploads;

    public string $maxCodAutoConfirmAmount = '0';

    public string $maxDeliveryAttempts = '3';

    public string $subscriptionMonthlyAmount = '0';

    public string $subscriptionAnnualAmount = '0';

    public string $platformCommissionRate = '0';

    public $logo = null;

    public ?string $currentLogoUrl = null;

    public function mount(): void
    {
        $this->maxCodAutoConfirmAmount = number_format((Setting::get('max_cod_auto_confirm_amount', config('markethub.max_cod_auto_confirm_amount'))) / 100, 2, '.', '');
        $this->maxDeliveryAttempts = (string) Setting::get('max_delivery_attempts', config('markethub.max_delivery_attempts'));
        $this->subscriptionMonthlyAmount = number_format((Setting::get('subscription_monthly_amount', config('subscriptions.plans.monthly.amount'))) / 100, 2, '.', '');
        $this->subscriptionAnnualAmount = number_format((Setting::get('subscription_annual_amount', config('subscriptions.plans.annual.amount'))) / 100, 2, '.', '');
        $this->platformCommissionRate = (string) Setting::get('platform_commission_rate', '0');

        $logoPath = Setting::get('app_logo_path');
        $this->currentLogoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : null;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'maxCodAutoConfirmAmount' => 'required|numeric|min:0',
            'maxDeliveryAttempts' => 'required|integer|min:1|max:10',
            'subscriptionMonthlyAmount' => 'required|numeric|min:0',
            'subscriptionAnnualAmount' => 'required|numeric|min:0',
            'platformCommissionRate' => 'required|numeric|min:0|max:100',
            'logo' => 'nullable|image|max:2048',
        ]);

        Setting::set('max_cod_auto_confirm_amount', (int) round($validated['maxCodAutoConfirmAmount'] * 100));
        Setting::set('max_delivery_attempts', (int) $validated['maxDeliveryAttempts']);
        Setting::set('subscription_monthly_amount', (int) round($validated['subscriptionMonthlyAmount'] * 100));
        Setting::set('subscription_annual_amount', (int) round($validated['subscriptionAnnualAmount'] * 100));
        Setting::set('platform_commission_rate', (float) $validated['platformCommissionRate']);

        if ($this->logo) {
            $path = $this->logo->store('branding', 'public');
            Setting::set('app_logo_path', $path);
            $this->currentLogoUrl = Storage::disk('public')->url($path);
            $this->logo = null;
        }

        AdminActionLog::create([
            'actor_id' => auth()->id(),
            'actor_name' => auth()->user()->name,
            'actor_email' => auth()->user()->email,
            'target_id' => auth()->id(),
            'target_name' => auth()->user()->name,
            'target_email' => auth()->user()->email,
            'action' => 'settings_updated',
            'changes' => ['after' => $validated],
        ]);

        session()->flash('settings-saved', true);
    }

    public function render()
    {
        return view('livewire.admin.business-settings');
    }
}
