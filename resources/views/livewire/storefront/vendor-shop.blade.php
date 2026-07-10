<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    @if (session('cart_message'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
            {{ session('cart_message') }}
        </div>
    @endif

    <div class="bg-green-700 text-white rounded-xl px-6 py-8 mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ $vendor->business_name }}</h1>
        @if ($vendor->lga || $vendor->state)
            <p class="mt-2 text-green-100">{{ $vendor->lga?->name }}{{ $vendor->lga && $vendor->state ? ', ' : '' }}{{ $vendor->state?->name }}</p>
        @endif
        <p class="mt-1 text-green-100 text-sm">{{ $products->total() }} product{{ $products->total() === 1 ? '' : 's' }} available &mdash; pay cash on delivery.</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        @forelse ($products as $product)
            <div class="bg-white rounded-lg shadow hover:shadow-md transition overflow-hidden flex flex-col">
                <a href="{{ route('storefront.product', $product->slug) }}" wire:navigate>
                    <div class="aspect-square bg-gray-100 flex items-center justify-center text-gray-300">
                        @if ($product->images->first())
                            <img src="{{ $product->images->first()->url() }}" class="object-cover w-full h-full" alt="{{ $product->name }}">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h16M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                        @endif
                    </div>
                </a>
                <div class="p-3 flex-1 flex flex-col">
                    <a href="{{ route('storefront.product', $product->slug) }}" wire:navigate class="text-sm font-medium text-gray-800 line-clamp-2 hover:text-green-700">
                        {{ $product->name }}
                    </a>
                    <div class="text-xs text-gray-400 mt-1">{{ $product->category->name }}</div>
                    <div class="mt-2 font-bold text-green-700">{{ naira($product->base_price) }}</div>
                    <button wire:click="addToCart({{ $product->id }})" class="mt-auto pt-3 w-full bg-green-700 hover:bg-green-800 text-white text-xs font-semibold py-2 rounded-md">
                        Add to Cart
                    </button>
                </div>
            </div>
        @empty
            <p class="col-span-full text-gray-500 text-sm py-12 text-center">This seller has no products listed yet.</p>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
</div>
