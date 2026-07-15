<x-mail::message>
# Cash remitted

Cash for order **#{{ $reconciliation->vendorOrder->order->order_number }}** has been remitted and
reconciled. It will be included in your next payout.

<x-mail::button :url="route('vendor.orders')">
View your orders
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
