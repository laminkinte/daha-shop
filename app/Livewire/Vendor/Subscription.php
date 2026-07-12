<?php

namespace App\Livewire\Vendor;

use App\Enums\PaymentGateway;
use App\Enums\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class Subscription extends Component
{
    public string $selectedPlan = 'monthly';

    public string $selectedGateway = 'paystack';

    public ?string $error = null;

    public function subscribe(SubscriptionService $subscriptions)
    {
        $vendor = Auth::user()->vendor;
        $plan = SubscriptionPlan::from($this->selectedPlan);
        $gateway = PaymentGateway::from($this->selectedGateway);
        $this->error = null;

        try {
            $url = $subscriptions->initialize($vendor, $plan, $gateway, route('vendor.subscription.callback'));
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'We could not start the payment right now. Please try again shortly.';

            return null;
        }

        return $this->redirect($url, navigate: false);
    }

    public function render()
    {
        $vendor = Auth::user()->vendor;

        return view('livewire.vendor.subscription', [
            'vendor' => $vendor,
            'activeSubscription' => $vendor->activeSubscription(),
            'history' => $vendor->subscriptions()->latest()->limit(10)->get(),
            'plans' => [
                'monthly' => ['label' => 'Monthly', 'amount' => config('subscriptions.plans.monthly.amount')],
                'annual' => ['label' => 'Annual', 'amount' => config('subscriptions.plans.annual.amount')],
            ],
        ]);
    }
}
