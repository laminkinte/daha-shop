<div class="max-w-sm mx-auto px-4 py-8">
    <div class="text-center mb-6">
        <h1 class="text-xl font-bold text-gray-900">Scan to Pay</h1>
        <p class="text-sm text-gray-500 mt-1">Point your camera at the payment code shown by your delivery agent or the vendor.</p>
    </div>

    <x-qr-scanner />

    <a href="{{ route('storefront.orders') }}" wire:navigate class="block text-center text-sm text-gray-500 hover:text-gray-700 mt-6">
        Cancel
    </a>
</div>
