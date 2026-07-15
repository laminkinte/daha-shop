<x-mail::message>
# Cash collected

Cash was collected for order **#{{ $vendorOrder->order->order_number }}**. It is now pending
reconciliation before payout.

<x-mail::button :url="route('vendor.orders')">
View your orders
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
