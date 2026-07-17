<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.dashboard')] class extends Component
{
    public function with(): array
    {
        $user = Auth::user();

        return [
            'user' => $user,
            'vendor' => $user->vendor,
            'deliveryAgent' => $user->deliveryAgent()->with('state', 'lga')->first(),
        ];
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Your Profile</h1>
        <p class="text-sm text-gray-500 mt-1">Manage your account details and security.</p>
    </div>

    <div class="space-y-6 max-w-2xl">
        <!-- Account overview -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start justify-between flex-wrap gap-2">
                <div>
                    <div class="font-semibold text-gray-800">{{ $user->name }}</div>
                    <div class="text-xs text-gray-500 mt-0.5 capitalize">{{ $user->role->value }} account</div>
                </div>
            </div>

            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Phone number</dt>
                    <dd class="flex items-center gap-2 font-medium text-gray-800">
                        {{ $user->phone }}
                        @if ($user->isPhoneVerified())
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Verified</span>
                        @else
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Not verified</span>
                        @endif
                    </dd>
                </div>

                @if ($user->uses_pin)
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Sign-in method</dt>
                        <dd class="font-medium text-gray-800">Phone number &amp; PIN</dd>
                    </div>
                @else
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Sign-in method</dt>
                        <dd class="font-medium text-gray-800">Email &amp; password</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <livewire:profile.update-profile-information-form />
        </div>

        @if ($vendor)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <livewire:profile.update-vendor-business-form />
            </div>
        @endif

        @if ($deliveryAgent)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800">Delivery Details</h2>
                <p class="mt-1 text-sm text-gray-500">Your assigned zone and vehicle, set up by an admin.</p>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Zone</dt>
                        <dd class="font-medium text-gray-800">
                            {{ $deliveryAgent->lga?->name }}, {{ $deliveryAgent->state?->name }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Vehicle</dt>
                        <dd class="font-medium text-gray-800 capitalize">{{ $deliveryAgent->vehicle_type ?? 'Not set' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Availability</dt>
                        <dd class="font-medium text-gray-800 capitalize">{{ $deliveryAgent->availability->value }}</dd>
                    </div>
                </dl>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            @if ($user->uses_pin)
                <livewire:profile.update-pin-form />
            @else
                <livewire:profile.update-password-form />
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</div>
