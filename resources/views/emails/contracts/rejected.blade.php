@component('mail::message')
# Contract Declined

**{{ $contract->client->company_name }}** declined contract **{{ $contract->reference }}**.

@if ($contract->rejection_reason)
> {{ $contract->rejection_reason }}
@endif

@component('mail::button', ['url' => $url])
View Contract
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
