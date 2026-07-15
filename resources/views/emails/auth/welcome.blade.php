<x-mail::message>
# Welcome to Daha Shop, {{ $user->name }}!

Your account is ready. We've also sent a verification code by SMS to
**{{ $user->phone }}** - enter it to confirm your phone number.

<x-mail::button :url="route('dashboard')">
Go to your dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
