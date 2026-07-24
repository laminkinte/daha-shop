<x-mail::message>
# Welcome to Daha Shop, {{ $vendor->business_name }}!

An admin has set up your vendor account and it's already approved &mdash; no ID verification needed.

**Email:** {{ $user->email }}<br>
**Temporary password:** {{ $password }}

<x-mail::button :url="route('login')">
Log in to your dashboard
</x-mail::button>

One more step before you can list products: you'll need an active subscription plan. You can subscribe from your vendor dashboard right after logging in. For security, please also change your password from your profile page.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
