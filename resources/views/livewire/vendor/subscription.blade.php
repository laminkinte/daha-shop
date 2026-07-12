<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Subscription</h2>
        <p class="text-sm text-gray-500 mt-1">An active subscription is required before you can post or list new products.</p>
    </div>

    @if (session('subscription_status') === 'activated')
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
            Payment confirmed — your subscription is now active.
        </div>
    @elseif (session('subscription_status') === 'failed')
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            That payment could not be confirmed. If you were charged, contact support — otherwise, please try again.
        </div>
    @endif

    @if ($error)
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $error }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-8">
        @if ($activeSubscription)
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ $activeSubscription->plan->label() }} plan active</p>
                    <p class="text-sm text-gray-500">Renews or expires on {{ $activeSubscription->expires_at->format('d M Y') }}</p>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">No active subscription</p>
                    <p class="text-sm text-gray-500">Subscribe below to start posting and listing products.</p>
                </div>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-8">
        <h3 class="font-semibold text-gray-800 mb-4">{{ $activeSubscription ? 'Renew or switch plan' : 'Choose a plan' }}</h3>

        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            <button type="button" wire:click="$set('selectedPlan', 'monthly')"
                class="text-left rounded-xl border-2 p-4 transition {{ $selectedPlan === 'monthly' ? 'border-green-600 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                <p class="font-semibold text-gray-900">{{ $plans['monthly']['label'] }}</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ naira($plans['monthly']['amount']) }}<span class="text-sm font-normal text-gray-500">/month</span></p>
            </button>
            <button type="button" wire:click="$set('selectedPlan', 'annual')"
                class="text-left rounded-xl border-2 p-4 transition relative {{ $selectedPlan === 'annual' ? 'border-green-600 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                <span class="absolute top-3 right-3 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-green-600 text-white">Save 2 months</span>
                <p class="font-semibold text-gray-900">{{ $plans['annual']['label'] }}</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ naira($plans['annual']['amount']) }}<span class="text-sm font-normal text-gray-500">/year</span></p>
            </button>
        </div>

        <div class="mb-6">
            <p class="text-sm font-medium text-gray-700 mb-2">Pay with</p>
            <div class="inline-flex rounded-lg border border-gray-200 p-1 bg-gray-50">
                <button type="button" wire:click="$set('selectedGateway', 'paystack')"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition {{ $selectedGateway === 'paystack' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    Paystack
                </button>
                <button type="button" wire:click="$set('selectedGateway', 'opay')"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition {{ $selectedGateway === 'opay' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    OPay
                </button>
            </div>
        </div>

        <button wire:click="subscribe" wire:loading.attr="disabled"
            class="w-full sm:w-auto bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg disabled:opacity-60">
            <span wire:loading.remove wire:target="subscribe">Pay with {{ $selectedGateway === 'opay' ? 'OPay' : 'Paystack' }} &rarr;</span>
            <span wire:loading wire:target="subscribe">Redirecting…</span>
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Payment History</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @php
                $statusStyles = [
                    'pending' => 'bg-amber-50 text-amber-700',
                    'active' => 'bg-emerald-50 text-emerald-700',
                    'expired' => 'bg-gray-100 text-gray-600',
                    'failed' => 'bg-red-50 text-red-700',
                    'cancelled' => 'bg-gray-100 text-gray-600',
                ];
            @endphp
            @forelse ($history as $subscription)
                <div class="px-5 py-3.5 flex items-center justify-between text-sm">
                    <div>
                        <span class="font-medium text-gray-900">{{ $subscription->plan->label() }}</span>
                        <span class="text-gray-400 ml-2">via {{ $subscription->gateway->label() }}</span>
                        <span class="text-gray-400 ml-2">{{ $subscription->created_at->format('d M Y, g:ia') }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-700">{{ naira($subscription->amount) }}</span>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $statusStyles[$subscription->status->value] ?? 'bg-gray-100 text-gray-700' }}">{{ $subscription->status->value }}</span>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-sm text-gray-500 text-center">No payments yet.</div>
            @endforelse
        </div>
    </div>
</div>
