<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Contact Us</h1>
    <p class="text-sm text-gray-500 mb-8">Have a question about an order, a delivery, or selling on Daha Shop? Reach us here.</p>

    {{--
        Sample contact details - replace with the real support channels
        before this page goes live.
    --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center text-green-700 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
            </div>
            <div class="text-sm font-semibold text-gray-800">Email</div>
            <a href="mailto:support@dahashop.ng" class="text-sm text-green-700 hover:underline">support@dahashop.ng</a>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center text-green-700 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
            </div>
            <div class="text-sm font-semibold text-gray-800">Phone / WhatsApp</div>
            <a href="tel:+2348000000000" class="text-sm text-green-700 hover:underline">+234 800 000 0000</a>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center text-green-700 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" /></svg>
            </div>
            <div class="text-sm font-semibold text-gray-800">Support Hours</div>
            <p class="text-sm text-gray-500">Mon&ndash;Sat, 8am&ndash;6pm WAT</p>
        </div>
    </div>

    <div class="mt-8 rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-xs text-amber-800">
        Already have an order? Check its status from <a href="{{ route('storefront.orders') }}" wire:navigate class="font-semibold underline">My Orders</a> &mdash; it's usually faster than contacting support.
    </div>
</div>
