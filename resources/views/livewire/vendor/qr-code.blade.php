<div class="max-w-xl">
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <h2 class="font-semibold text-gray-800 mb-1">Your Shop QR Code</h2>
        <p class="text-sm text-gray-500 mb-6">
            Customers who scan this code go straight to your shop page &mdash; showing only
            <strong>{{ $vendor->business_name }}</strong>'s products, nothing else. Print it on packaging,
            flyers, or a shop sign.
        </p>

        <div class="inline-block border rounded-lg p-4 bg-white">
            <img src="{{ $qrDataUri }}" alt="QR code linking to {{ $vendor->business_name }}" class="mx-auto" width="300" height="300">
        </div>

        <div class="mt-6">
            <label class="text-xs font-medium text-gray-500 uppercase">Your shop link</label>
            <div class="mt-1 flex items-center gap-2">
                <input type="text" readonly value="{{ $shopUrl }}" onclick="this.select()" class="flex-1 text-sm rounded-md border-gray-300 text-gray-600">
                <a href="{{ $shopUrl }}" target="_blank" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-md whitespace-nowrap">
                    Preview
                </a>
            </div>
        </div>

        <a href="{{ $qrDataUri }}" download="{{ \Illuminate\Support\Str::slug($vendor->business_name) }}-qr-code.png"
            class="mt-6 inline-block w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-md">
            Download QR Code (PNG)
        </a>
    </div>
</div>
