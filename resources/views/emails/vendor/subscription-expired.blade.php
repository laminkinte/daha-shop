<x-mail::message>
# Subscription expired

Your Daha Shop subscription expired on **{{ $subscription->expires_at->format('M j, Y') }}**.
Renew it to keep posting and listing new products.

<x-mail::button :url="route('vendor.subscription')">
Renew subscription
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
