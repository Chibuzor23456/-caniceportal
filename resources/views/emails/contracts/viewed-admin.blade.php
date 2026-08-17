@component('mail::message')
# Contract Viewed

**{{ $contract->client->company_name }}** just viewed contract **{{ $contract->reference }}**.

@component('mail::button', ['url' => $url])
View Contract
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
