<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">About Daha Shop</h1>

    <div class="prose prose-sm max-w-none text-gray-600 space-y-4">
        <p>
            Daha Shop is a cash-on-delivery marketplace built for Nigerian buyers and sellers.
            No card, no upfront payment &mdash; browse products from verified sellers across the
            country, place your order, and pay cash when it arrives at your door.
        </p>
        <p>
            Every order is confirmed by SMS, and once it's out for delivery you can track its
            live location on a map, right up to the moment your delivery agent (or the seller
            themselves, for local deliveries) arrives.
        </p>
        <p>
            Sellers on Daha Shop go through an identity verification process before they're
            approved to list products, so you're always buying from a real, verified business.
        </p>
    </div>

    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('storefront.home') }}" wire:navigate class="bg-green-700 hover:bg-green-800 text-white font-semibold px-5 py-2.5 rounded-lg transition-colors">
            Start Shopping
        </a>
        <a href="{{ route('register') }}?as=seller" wire:navigate class="border border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold px-5 py-2.5 rounded-lg transition-colors">
            Become a Seller
        </a>
    </div>
</div>
