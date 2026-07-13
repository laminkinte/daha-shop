<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">My Wishlist</h1>

    @if ($items->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
            <p class="text-gray-500 mt-3">Your wishlist is empty.</p>
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($items as $wishlistItem)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <a href="{{ route('storefront.product', $wishlistItem->product->slug) }}" wire:navigate>
                        <div class="aspect-square bg-gray-100 flex items-center justify-center text-gray-300">
                            @if ($wishlistItem->product->images->first())
                                <img src="{{ $wishlistItem->product->images->first()->url() }}" class="object-cover w-full h-full">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h16M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                            @endif
                        </div>
                    </a>
                    <div class="p-3">
                        <div class="text-sm font-medium text-gray-800 line-clamp-2">{{ $wishlistItem->product->name }}</div>
                        <div class="text-green-700 font-semibold mt-1">{{ naira($wishlistItem->product->base_price) }}</div>
                        <div class="flex gap-2 mt-3">
                            <button wire:click="addToCart({{ $wishlistItem->product->id }})" class="flex-1 text-xs bg-green-700 hover:bg-green-800 text-white rounded-lg py-1.5 transition-colors">Add to Cart</button>
                            <button wire:click="remove({{ $wishlistItem->id }})" class="text-xs border border-gray-300 rounded-lg px-2 text-gray-500 hover:bg-gray-50 transition-colors">Remove</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
