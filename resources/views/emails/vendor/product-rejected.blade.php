<x-mail::message>
# Your product needs changes

Your product **{{ $product->name }}** was rejected: {{ $product->rejection_reason }}

Update it and submit it for review again.

<x-mail::button :url="route('vendor.products')">
Edit your product
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
