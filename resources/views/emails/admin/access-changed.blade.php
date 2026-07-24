<x-mail::message>
# Your admin access has changed

Hi {{ $user->name }}, an update was made to your Daha Shop admin account:

**{{ $summary }}**

<x-mail::button :url="route('login')">
Log in
</x-mail::button>

If you weren't expecting this change, contact another admin on your team right away.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
