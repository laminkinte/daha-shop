<x-mail::message>
# You've been added as an admin, {{ $user->name }}

An admin account has been created for you on Daha Shop.

**Email:** {{ $user->email }}<br>
**Temporary password:** {{ $password }}

<x-mail::button :url="route('login')">
Log in
</x-mail::button>

For security, please change your password from your profile page as soon as you log in.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
