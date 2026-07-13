<div class="max-w-xl">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 text-center">
        <h2 class="font-semibold text-gray-800 mb-1">Your Shop QR Code</h2>
        <p class="text-sm text-gray-500 mb-6">
            Customers who scan this code go straight to your shop page &mdash; showing only
            <strong>{{ $vendor->business_name }}</strong>'s products, nothing else. Print it on packaging,
            flyers, or a shop sign.
        </p>

        <div class="inline-block border border-gray-100 rounded-xl p-4 bg-white">
            <img src="{{ $qrDataUri }}" alt="QR code linking to {{ $vendor->business_name }}" class="mx-auto" width="300" height="300">
        </div>

        <div class="mt-6">
            <label class="text-xs font-medium text-gray-500 uppercase">Your shop link</label>
            <div class="mt-1 flex items-center gap-2">
                <input type="text" readonly value="{{ $shopUrl }}" onclick="this.select()" class="flex-1 text-sm rounded-lg border-gray-300 text-gray-600 focus:border-green-500 focus:ring-green-500">
                <a href="{{ $shopUrl }}" target="_blank" class="text-xs bg-green-50 hover:bg-green-100 text-green-700 px-3 py-2 rounded-lg whitespace-nowrap transition-colors">
                    Preview
                </a>
            </div>
        </div>

        <a href="{{ $qrDataUri }}" download="{{ \Illuminate\Support\Str::slug($vendor->business_name) }}-qr-code.png"
            class="mt-6 inline-block w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg transition-colors">
            Download QR Code (PNG)
        </a>
    </div>
</div>
