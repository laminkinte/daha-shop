<x-mail::message>
# Order cancelled

Your order **#{{ $order->order_number }}** has been cancelled: {{ $order->cancellation_reason }}

<x-mail::button :url="route('storefront.orders.show', $order)">
View order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
