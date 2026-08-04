<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Checkout</h1>

    @if ($errorMessage)
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border-2 border-green-200 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                    <h2 class="font-semibold text-gray-800">Confirm Delivery Address</h2>
                </div>
                <p class="text-xs text-gray-500 mb-4">This is where your order will be sent - double-check it before placing your order.</p>

                @if ($addresses->isNotEmpty())
                    <div class="space-y-2 mb-4">
                        @foreach ($addresses as $address)
                            @php $isSelected = !$useNewAddress && $selectedAddressId === $address->id; @endphp
                            <label class="flex items-start gap-3 border-2 rounded-lg p-3 cursor-pointer transition-colors {{ $isSelected ? 'border-green-600 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:click="$set('useNewAddress', false)" wire:model="selectedAddressId" value="{{ $address->id }}" class="mt-1">
                                <span class="text-sm flex-1">
                                    <span class="font-medium">{{ $address->label }}</span> &mdash;
                                    {{ $address->street_address }}, {{ $address->area }}, {{ $address->lga->name }}, {{ $address->state->name }}
                                    <br><span class="text-gray-500">{{ $address->phone }}</span>
                                </span>
                                @if ($isSelected)
                                    <span class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold text-green-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                        Selected
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endif

                <button wire:click="$set('useNewAddress', true)" class="text-sm text-green-700 font-medium hover:underline">
                    + Use a new address
                </button>

                @if ($useNewAddress)
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">State</label>
                            <select wire:model.live="stateId" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                <option value="">Select state</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                            @error('stateId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">LGA</label>
                            <select wire:model.live="lgaId" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                <option value="">Select LGA</option>
                                @foreach ($this->lgas as $lga)
                                    <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                @endforeach
                            </select>
                            @error('lgaId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-sm font-medium text-gray-700">Area / Neighborhood</label>
                            <input type="text" wire:model="area" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            @error('area') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-sm font-medium text-gray-700">Street Address</label>
                            <textarea wire:model="streetAddress" rows="2" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500"></textarea>
                            @error('streetAddress') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="text" wire:model="phone" placeholder="+234..." class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            @error('phone') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Landmark (optional)</label>
                            <input type="text" wire:model="landmark" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800 flex gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                <span>We'll send a one-time SMS code to confirm this order before your vendor starts packing it &mdash; this protects you and the seller from fake orders.</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 h-fit">
            <h2 class="font-semibold text-gray-800 mb-4">Order Summary</h2>

            @if ($this->feePreview['unavailable'])
                <p class="text-sm text-red-600 mb-3">Delivery isn't available to this address yet for one or more vendors &mdash; choose pickup instead.</p>
            @endif

            <div class="space-y-3 text-sm">
                @foreach ($this->feePreview['lines'] as $line)
                    <div>
                        <div class="flex justify-between text-gray-600">
                            <span>{{ $line['vendor'] }}</span>
                            <span>{{ naira($line['subtotal']) }} {{ $line['pickup'] ? '' : '+ '.naira($line['fee']).' delivery' }}</span>
                        </div>
                        <div class="mt-1 flex items-center gap-3 text-xs">
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" wire:model.live="fulfillmentMethods.{{ $line['vendor_id'] }}" value="delivery" class="text-green-700 focus:ring-green-500">
                                Deliver to my address
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="radio" wire:model.live="fulfillmentMethods.{{ $line['vendor_id'] }}" value="pickup" class="text-green-700 focus:ring-green-500">
                                Pick up myself
                            </label>
                        </div>
                        @if ($line['pickup'])
                            <p class="mt-1 text-xs text-gray-400">Pickup from: {{ $line['vendor_address'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="border-t mt-4 pt-4 space-y-1 text-sm">
                <div class="flex justify-between font-bold text-gray-900 text-base">
                    <span>Pay Cash on Delivery</span><span>{{ naira($this->feePreview['itemsSubtotal']) }}</span>
                </div>
                @if ($this->feePreview['deliveryTotal'] > 0)
                    <div class="flex justify-between text-gray-600 pt-1">
                        <span>Pay Now via OPay (delivery fee)</span><span>{{ naira($this->feePreview['deliveryTotal']) }}</span>
                    </div>
                @endif
            </div>

            @if ($this->feePreview['deliveryTotal'] > 0)
                <p class="mt-3 text-xs text-gray-400">You'll pay the delivery fee online via OPay on the next step, before your order is confirmed. Items stay cash on delivery, as usual.</p>
            @endif

            <button wire:click="placeOrder" wire:loading.attr="disabled" class="mt-6 w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-60">
                Place Order
            </button>
        </div>
    </div>
</div>
