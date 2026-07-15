<x-mail::message>
# We couldn't deliver part of your order

We could not deliver part of your order **#{{ $vendorOrder->order->order_number }}**
({{ $vendorOrder->failure_reason }}). We will retry or contact you shortly.

<x-mail::button :url="route('storefront.orders.show', $vendorOrder->order)">
View your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
