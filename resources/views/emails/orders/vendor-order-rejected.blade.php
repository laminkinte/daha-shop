<x-mail::message>
# Part of your order could not be fulfilled

**{{ $vendorOrder->vendor->business_name }}** could not fulfil part of your order
**#{{ $vendorOrder->order->order_number }}** ({{ $vendorOrder->failure_reason }}). That item has
been refunded to stock and you will not be charged for it.

<x-mail::button :url="route('storefront.orders.show', $vendorOrder->order)">
View order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
