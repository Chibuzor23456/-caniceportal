@component('mail::message')
# New Client Onboarded

**{{ $client->company_name }}** ({{ $client->contact_person }}) was added and onboarded automatically.

@component('mail::button', ['url' => $url])
View Client
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
