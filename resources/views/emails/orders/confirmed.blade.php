<x-mail::message>
# Order confirmed

Your order **#{{ $order->order_number }}** is confirmed and being processed.

Remember: this is Cash on Delivery - please have the exact amount ready when your order arrives.

<x-mail::button :url="route('storefront.orders.show', $order)">
Track your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
