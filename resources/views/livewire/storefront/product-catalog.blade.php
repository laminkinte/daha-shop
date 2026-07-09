<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    @if (session('cart_message'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
            {{ session('cart_message') }}
        </div>
    @endif

    <div class="bg-green-700 text-white rounded-xl px-6 py-8 mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold">Shop Nigeria's marketplace &mdash; pay when it arrives.</h1>
        <p class="mt-2 text-green-100">No card needed. Confirm by SMS, pay cash on delivery.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <aside class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Categories</h2>
                <ul class="space-y-1 text-sm">
                    <li>
                        <button wire:click="$set('category', null)" class="{{ !$this->category ? 'text-green-700 font-semibold' : 'text-gray-600' }} hover:text-green-700">
                            All Categories
                        </button>
                    </li>
                    @foreach ($categories as $cat)
                        <li>
                            <button wire:click="$set('category', {{ $cat->id }})" class="{{ $this->category === $cat->id ? 'text-green-700 font-semibold' : 'text-gray-600' }} hover:text-green-700">
                                {{ $cat->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        <div class="lg:col-span-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div class="text-sm text-gray-500">{{ $products->total() }} products found</div>
                <select wire:model.live="sort" class="rounded-md border-gray-300 text-sm">
                    <option value="newest">Newest</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                </select>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
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
                            <div class="text-xs text-gray-400 mt-1">{{ $product->vendor->business_name }}</div>
                            <div class="mt-2 font-bold text-green-700">{{ naira($product->base_price) }}</div>
                            <button wire:click="addToCart({{ $product->id }})" class="mt-auto pt-3 w-full bg-green-700 hover:bg-green-800 text-white text-xs font-semibold py-2 rounded-md">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full text-gray-500 text-sm py-12 text-center">No products found.</p>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
