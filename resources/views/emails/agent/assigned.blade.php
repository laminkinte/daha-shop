<x-mail::message>
# New delivery assigned

You've been assigned to deliver order **#{{ $vendorOrder->order->order_number }}** from
**{{ $vendorOrder->vendor->business_name }}**.

<x-mail::button :url="route('agent.deliveries.show', $vendorOrder->id)">
View delivery
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
