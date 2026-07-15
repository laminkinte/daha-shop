<x-mail::message>
# Your order is being prepared

**{{ $vendorOrder->vendor->business_name }}** has accepted your order
**#{{ $vendorOrder->order->order_number }}** and is preparing it.

<x-mail::button :url="route('storefront.orders.show', $vendorOrder->order)">
Track your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
