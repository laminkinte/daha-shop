<x-mail::message>
# Payout paid

Your Daha Shop payout of **{{ naira($payout->total_amount) }}** for
{{ $payout->period_start->format('M j') }}–{{ $payout->period_end->format('M j') }} has been paid.

<x-mail::button :url="route('vendor.payouts')">
View payout history
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
