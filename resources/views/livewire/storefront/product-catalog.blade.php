<div>
    <!-- Hero -->
    <section class="bg-gradient-to-br from-green-700 to-green-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">
                    Shop now, pay on delivery.
                </h1>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-green-100">
                    <span class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Cash on delivery
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        {{ $stats['vendors'] }}+ verified sellers
                    </span>
                    <a href="{{ route('register') }}?as=seller" wire:navigate class="bg-white/10 hover:bg-white/20 border border-white/30 text-white font-semibold px-3 py-1.5 rounded-full transition-colors">
                        Become a Seller
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" id="products">

        @if (session('cart_message'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                {{ session('cart_message') }}
            </div>
        @endif

        <div class="mb-8">
            <h2 class="font-semibold text-gray-800 mb-3">Shop by Category</h2>
            <div class="flex gap-3 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-none">
                <button wire:click="$set('category', null)"
                    class="flex flex-col items-center gap-2 shrink-0 w-20 group">
                    <span class="h-14 w-14 rounded-full flex items-center justify-center border-2 transition {{ !$this->category ? 'bg-green-700 border-green-700 text-white' : 'bg-white border-gray-200 text-gray-500 group-hover:border-green-600 group-hover:text-green-700' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                    </span>
                    <span class="text-xs font-medium {{ !$this->category ? 'text-green-700' : 'text-gray-600' }} text-center">All</span>
                </button>

                @foreach ($categories as $cat)
                    <button wire:click="$set('category', {{ $cat->id }})"
                        class="flex flex-col items-center gap-2 shrink-0 w-20 group">
                        <span class="h-14 w-14 rounded-full flex items-center justify-center border-2 transition {{ $this->category === $cat->id ? 'bg-green-700 border-green-700 text-white' : 'bg-white border-gray-200 text-gray-500 group-hover:border-green-600 group-hover:text-green-700' }}">
                            <x-category-icon :slug="$cat->slug" />
                        </span>
                        <span class="text-xs font-medium {{ $this->category === $cat->id ? 'text-green-700' : 'text-gray-600' }} text-center leading-tight">{{ $cat->name }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div class="text-sm text-gray-500">{{ $products->total() }} products found</div>
            <select wire:model.live="sort" class="rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500">
                <option value="newest">Newest</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
            </select>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @forelse ($products as $product)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
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
                        <button wire:click="addToCart({{ $product->id }})" class="mt-auto pt-3 w-full bg-green-700 hover:bg-green-800 text-white text-xs font-semibold py-2 rounded-lg transition-colors">
                            Add to Cart
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-16">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" /></svg>
                    <p class="text-gray-500 text-sm mt-3">No products found.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    </div>
</div>
