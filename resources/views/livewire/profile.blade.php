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
    <!-- Profile hero -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6 flex items-center gap-4">
        <div class="h-16 w-16 rounded-full bg-green-700 flex items-center justify-center text-2xl font-bold text-white shrink-0">
            {{ Str::of($user->name)->substr(0, 1)->upper() }}
        </div>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 truncate">{{ $user->name }}</h1>
            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-green-50 text-green-700 capitalize">{{ $user->role->value }} account</span>
                @if ($user->isPhoneVerified())
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Phone verified</span>
                @else
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Phone not verified</span>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-6 max-w-2xl">
        <!-- Account overview -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="h-9 w-9 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                </div>
                <div>
                    <h2 class="font-semibold text-gray-800">Account Overview</h2>
                    <p class="text-xs text-gray-500">How you sign in to Daha Shop.</p>
                </div>
            </div>
            <div class="p-6">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Phone number</dt>
                        <dd class="font-medium text-gray-800">{{ $user->phone }}</dd>
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
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <livewire:profile.update-profile-information-form />
        </div>

        @if ($vendor)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <livewire:profile.update-vendor-business-form />
            </div>
        @endif

        @if ($deliveryAgent)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="h-9 w-9 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h5.401c.585 0 1.09.408 1.212.98l1.244 5.85c.084.399-.012.812-.264 1.129M14.25 7.5v8.25m-6-8.25H3.375c-.621 0-1.125.504-1.125 1.125v9.75c0 .621.504 1.125 1.125 1.125h1.5" /></svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-gray-800">Delivery Details</h2>
                        <p class="text-xs text-gray-500">Your assigned zone and vehicle, set up by an admin.</p>
                    </div>
                </div>
                <div class="p-6">
                    <dl class="space-y-3 text-sm">
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
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            @if ($user->uses_pin)
                <livewire:profile.update-pin-form />
            @else
                <livewire:profile.update-password-form />
            @endif
        </div>

        <div class="bg-white rounded-xl border border-red-100 shadow-sm overflow-hidden">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</div>
