<x-mail::message>
# Retake needed: {{ $documentLabel }}

Hi {{ $businessName }},

An admin reviewed your {{ $documentLabel }} photo and it needs to be retaken before your seller
account can be approved.

**Reason:** {{ $reason }}

<x-mail::button :url="route('vendor.identity')">
Resubmit your photo
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
