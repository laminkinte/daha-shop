<div>
    <x-storefront.page-hero
        badge="Nigeria's Cash-on-Delivery Marketplace"
        title="About Daha Shop"
        subtitle="No card, no upfront payment. Browse products from verified sellers across the country, place your order, and pay cash when it arrives at your door."
    />

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-14 sm:-mt-16 relative z-10 pb-16">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6"
            x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 150)">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-lg p-6 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" style="transition-delay: 0ms">
                <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-green-600 to-emerald-500 flex items-center justify-center text-white mb-4 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.286z" /></svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1.5">Verified Sellers</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Every seller goes through identity verification &mdash; a valid ID and a live selfie &mdash; before an admin approves them to list products.</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-lg p-6 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" style="transition-delay: 100ms">
                <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-green-600 to-emerald-500 flex items-center justify-center text-white mb-4 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1.5">Live Order Tracking</h3>
                <p class="text-sm text-gray-500 leading-relaxed">Once your order is out for delivery, track your agent's live location on a map, right up to the moment they arrive.</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-lg p-6 transition-all duration-500 hover:-translate-y-1 hover:shadow-xl"
                :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" style="transition-delay: 200ms">
                <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-green-600 to-emerald-500 flex items-center justify-center text-white mb-4 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1.5">Pay When It Arrives</h3>
                <p class="text-sm text-gray-500 leading-relaxed">No card, no upfront payment required for your items &mdash; pay cash directly to the delivery agent or seller when your order shows up.</p>
            </div>
        </div>

        <div class="mt-10 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Our Story</h2>
            <div class="text-sm text-gray-600 leading-relaxed space-y-4">
                <p>
                    Daha Shop is a cash-on-delivery marketplace built for Nigerian buyers and sellers.
                    Every order is confirmed by SMS the moment it's placed, so you always know it went through.
                </p>
                <p>
                    We built this because paying upfront online can feel risky when you haven't seen the product
                    yet, and many sellers don't have the tools to reach buyers beyond their neighbourhood. Daha
                    Shop bridges that gap &mdash; buyers get the certainty of paying only once the order is in
                    their hands, and sellers get access to customers across the country.
                </p>
            </div>
        </div>

        <div class="mt-10 relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 to-gray-800 px-6 py-10 sm:px-10 text-center">
            <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-green-500/20 blur-3xl pointer-events-none"></div>
            <h2 class="text-xl sm:text-2xl font-bold text-white relative">Ready to get started?</h2>
            <p class="text-gray-400 text-sm mt-2 relative">Shop with confidence, or open your own storefront in minutes.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3 relative">
                <a href="{{ route('storefront.home') }}" wire:navigate class="bg-green-600 hover:bg-green-500 text-white font-semibold px-6 py-2.5 rounded-lg transition-all hover:scale-[1.03] shadow-lg shadow-green-900/30">
                    Start Shopping
                </a>
                <a href="{{ route('register') }}?as=seller" wire:navigate class="bg-white/10 hover:bg-white/20 text-white font-semibold px-6 py-2.5 rounded-lg transition-all hover:scale-[1.03] ring-1 ring-white/20">
                    Become a Seller
                </a>
            </div>
        </div>
    </div>
</div>
