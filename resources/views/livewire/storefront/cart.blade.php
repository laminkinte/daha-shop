<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Your Cart</h1>

    @if ($items->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
            <p class="text-gray-500 mt-3">Your cart is empty.</p>
            <div class="mt-4">
                <a href="{{ route('storefront.home') }}" wire:navigate class="text-green-700 font-medium hover:underline">Continue shopping &rarr;</a>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-100">
            @foreach ($items as $item)
                <div class="flex items-center gap-4 p-4">
                    <div class="h-16 w-16 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300 shrink-0 overflow-hidden">
                        @if ($item->product->images->first())
                            <img src="{{ $item->product->images->first()->url() }}" class="object-cover w-full h-full">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h16M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-800 truncate">{{ $item->product->name }}</div>
                        <div class="text-xs text-gray-400">{{ $item->product->vendor->business_name }}</div>
                        <div class="text-sm text-green-700 font-semibold mt-1">{{ naira($item->unitPrice()) }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" min="1" value="{{ $item->quantity }}"
                            wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                            class="w-16 rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                        <button wire:click="removeItem({{ $item->id }})" class="h-8 w-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="w-24 text-right font-semibold text-gray-800">
                        {{ naira($item->unitPrice() * $item->quantity) }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 bg-white rounded-xl border border-gray-100 shadow-sm p-6 flex items-center justify-between">
            <div>
                <div class="text-sm text-gray-500">Subtotal (delivery fee calculated at checkout)</div>
                <div class="text-2xl font-bold text-gray-900">{{ naira($subtotal) }}</div>
            </div>
            <a href="{{ route('storefront.checkout') }}" wire:navigate class="bg-green-700 hover:bg-green-800 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                Proceed to Checkout
            </a>
        </div>
    @endif
</div>
