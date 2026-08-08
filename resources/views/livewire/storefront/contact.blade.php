<div>
    <x-storefront.page-hero
        badge="We're here to help"
        title="Contact Us"
        subtitle="Have a question about an order, a delivery, or selling on Daha Shop? Reach us through any of the channels below."
    />

    {{--
        Sample contact details - replace with the real support channels
        before this page goes live.
    --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-14 sm:-mt-16 relative z-10 pb-16">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
            <div x-data="{ copied: false }" class="group bg-white rounded-2xl border border-gray-100 shadow-lg p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-green-600 to-emerald-500 flex items-center justify-center text-white mb-4 shadow-sm transition-transform group-hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                </div>
                <div class="text-sm font-semibold text-gray-800">Email</div>
                <div class="flex items-center gap-2 mt-1">
                    <a href="mailto:support@dahashop.ng" class="text-sm text-green-700 hover:underline">support@dahashop.ng</a>
                    <button type="button"
                        @click="navigator.clipboard.writeText('support@dahashop.ng'); copied = true; setTimeout(() => copied = false, 1500)"
                        class="text-gray-300 hover:text-green-600 transition-colors" title="Copy email">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" /></svg>
                    </button>
                    <span x-show="copied" x-transition x-cloak class="text-xs text-green-600 font-medium">Copied!</span>
                </div>
            </div>

            <div x-data="{ copied: false }" class="group bg-white rounded-2xl border border-gray-100 shadow-lg p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-green-600 to-emerald-500 flex items-center justify-center text-white mb-4 shadow-sm transition-transform group-hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                </div>
                <div class="text-sm font-semibold text-gray-800">Phone / WhatsApp</div>
                <div class="flex items-center gap-2 mt-1">
                    <a href="tel:+2348000000000" class="text-sm text-green-700 hover:underline">+234 800 000 0000</a>
                    <button type="button"
                        @click="navigator.clipboard.writeText('+2348000000000'); copied = true; setTimeout(() => copied = false, 1500)"
                        class="text-gray-300 hover:text-green-600 transition-colors" title="Copy number">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" /></svg>
                    </button>
                    <span x-show="copied" x-transition x-cloak class="text-xs text-green-600 font-medium">Copied!</span>
                </div>
            </div>

            <div class="group bg-white rounded-2xl border border-gray-100 shadow-lg p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-green-600 to-emerald-500 flex items-center justify-center text-white mb-4 shadow-sm transition-transform group-hover:scale-110">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" /></svg>
                </div>
                <div class="text-sm font-semibold text-gray-800">Support Hours</div>
                <p class="text-sm text-gray-500 mt-1">Mon&ndash;Sat, 8am&ndash;6pm WAT</p>
            </div>
        </div>

        <div class="mt-8 flex items-center gap-3 rounded-xl bg-amber-50 border border-amber-100 px-4 py-3.5 text-sm text-amber-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
            <span>Already have an order? Check its status from <a href="{{ route('storefront.orders') }}" wire:navigate class="font-semibold underline">My Orders</a> &mdash; it's usually faster than contacting support.</span>
        </div>
    </div>
</div>
