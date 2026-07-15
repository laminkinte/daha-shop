<x-mail::message>
# Your product is now live

Your product **{{ $product->name }}** was approved and is now visible to customers.

<x-mail::button :url="route('storefront.product', $product)">
View your listing
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
