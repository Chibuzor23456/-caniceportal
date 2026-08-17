@component('mail::message')
# Contract Accepted

@if ($forAdmin)
**{{ $contract->client->company_name }}** just accepted contract **{{ $contract->reference }}**.
@else
Thanks for accepting contract **{{ $contract->reference }}**. Your signed copy is available any time in your portal.
@endif

@component('mail::button', ['url' => $url])
View Signed Contract
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
