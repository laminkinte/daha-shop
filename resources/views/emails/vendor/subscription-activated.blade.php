<x-mail::message>
# Subscription active

Your Daha Shop subscription is now active until **{{ $subscription->expires_at->format('M j, Y') }}**.
You can post and list new products.

<x-mail::button :url="route('vendor.subscription')">
View subscription
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
