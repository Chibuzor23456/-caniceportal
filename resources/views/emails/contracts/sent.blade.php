@component('mail::message')
# New Contract

You have a new contract from Canice Technologies: **{{ $contract->title }}** ({{ $contract->reference }}).

@component('mail::button', ['url' => $secureUrl])
Review & Sign
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
