<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Test Payment (Dev Only)</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50 min-h-screen flex items-center justify-center p-6">
        <div class="max-w-sm w-full bg-white rounded-xl border border-gray-100 shadow-sm p-6 text-center">
            <span class="inline-block text-xs font-bold tracking-wide text-white bg-gray-500 rounded-full px-2 py-0.5 mb-4">TEST (DEV ONLY)</span>

            @if ($awaitingConfirmation)
                <h1 class="font-semibold text-lg mb-1">Simulate Customer Payment</h1>
                <p class="text-sm text-gray-500 mb-6">
                    This stands in for a real gateway's checkout page. Click below to simulate the customer
                    completing payment &mdash; the order updates automatically once you do.
                </p>
                <form method="POST" action="{{ route('test-payment.pay', $reference) }}">
                    @csrf
                    <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg transition-colors">
                        Simulate Payment
                    </button>
                </form>
            @else
                <div class="text-green-600 text-4xl mb-3">&#10003;</div>
                <h1 class="font-semibold text-lg mb-1">
                    {{ $vendorOrder?->isPickup() ? 'Order Picked Up!' : 'Order Delivered!' }}
                </h1>
                <p class="text-sm text-gray-500 mb-4">Payment confirmed &mdash; you can close this page now.</p>
                @if ($vendorOrder)
                    <a href="{{ route('storefront.orders.show', $vendorOrder->order->order_number) }}" class="text-sm font-semibold text-green-700 underline">
                        View your order
                    </a>
                @endif
            @endif
        </div>
    </body>
</html>
