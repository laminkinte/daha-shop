<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-1">Privacy Policy</h1>
    <p class="text-xs text-gray-400 mb-8">Last updated {{ now()->format('F Y') }}</p>

    <div class="prose prose-sm max-w-none text-gray-600 space-y-6">
        <section>
            <h2 class="text-base font-semibold text-gray-800 mb-2">Information We Collect</h2>
            <p>To process your orders, we collect your name, phone number, email (if provided), delivery address, and order history. Sellers additionally submit an ID document and a live selfie, used only for identity verification.</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-800 mb-2">How We Use It</h2>
            <p>Your information is used to confirm and fulfil orders, share your delivery address and live location with the assigned delivery agent for that order, send order-status SMS/email updates, and verify seller identities before approval.</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-800 mb-2">Location Data</h2>
            <p>When an order is out for delivery, the delivery agent's (or self-delivering seller's) live location is shared with you so you can track your order. This location is only visible to the customer on that specific order, and only while it's actively out for delivery.</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-800 mb-2">Sharing</h2>
            <p>We don't sell your personal information. It's shared only with the seller and delivery agent fulfilling your order, and with payment providers when processing a digital payment (such as a delivery fee).</p>
        </section>
        <section>
            <h2 class="text-base font-semibold text-gray-800 mb-2">Contact</h2>
            <p>Questions about your data? Reach out via our <a href="{{ route('storefront.contact') }}" wire:navigate class="text-green-700 font-semibold hover:underline">Contact page</a>.</p>
        </section>
    </div>
</div>
