<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    @if (session('cart_message'))
        <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
            {{ session('cart_message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm aspect-square flex items-center justify-center text-gray-300 overflow-hidden">
            @if ($product->images->first())
                <img src="{{ $product->images->first()->url() }}" class="object-cover w-full h-full" alt="{{ $product->name }}">
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h16M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
            @endif
        </div>

        <div>
            <p class="text-sm text-gray-400">{{ $product->category->name }}</p>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $product->name }}</h1>
            <a href="{{ route('storefront.vendor', $product->vendor->slug) }}" wire:navigate class="text-sm text-green-700 hover:underline">{{ $product->vendor->business_name }}</a>

            <div class="mt-4 text-3xl font-bold text-green-700">{{ naira($this->unitPrice) }}</div>

            <p class="mt-4 text-gray-600 text-sm leading-relaxed">{{ $product->description }}</p>

            @if ($product->variants->isNotEmpty())
                <div class="mt-4">
                    <label class="text-sm font-medium text-gray-700">Variant</label>
                    <select wire:model="variantId" class="mt-1 block w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                        @foreach ($product->variants as $variant)
                            <option value="{{ $variant->id }}">{{ collect($variant->attributes)->map(fn($v,$k) => "$k: $v")->implode(', ') ?: $variant->sku }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="mt-4 flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Qty</label>
                <input type="number" wire:model="quantity" min="1" class="w-20 rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
            </div>

            <div class="mt-6 flex gap-3">
                <button wire:click="addToCart" class="flex-1 bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg transition-colors">
                    Add to Cart
                </button>
                <button wire:click="toggleWishlist" class="border border-gray-300 rounded-lg px-4 py-3 transition-colors {{ $this->inWishlist ? 'text-red-600 border-red-300 bg-red-50' : 'text-gray-500 hover:bg-gray-50' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="{{ $this->inWishlist ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                </button>
            </div>

            <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-2 text-xs text-emerald-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Cash on Delivery available &mdash; pay when your order arrives.
            </div>
        </div>
    </div>

    @if ($product->reviews->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Customer Reviews</h2>
            <div class="space-y-4">
                @foreach ($product->reviews as $review)
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-sm text-gray-800">{{ $review->user->name }}</span>
                            <span class="text-amber-500 text-sm">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                        </div>
                        @if ($review->comment)
                            <p class="text-sm text-gray-600 mt-2">{{ $review->comment }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
