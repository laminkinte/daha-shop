<x-mail::message>
# Order rejected

Your order **#{{ $order->order_number }}** was rejected: {{ $order->cancellation_reason }}

Any reserved stock has been released. No payment was collected for this order.

<x-mail::button :url="route('storefront.orders.show', $order)">
View order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
