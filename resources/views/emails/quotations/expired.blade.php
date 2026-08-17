@component('mail::message')
# Quotation {{ $quotation->reference }} Has Expired

@if ($forAdmin)
Quotation **{{ $quotation->reference }}** for **{{ $quotation->client->company_name }}** has expired without a decision.
@else
Hi {{ $quotation->client->contact_person }},

Your quotation from Canice Technologies has expired. If you're still interested, you can request a revision and a new quotation will be prepared for you.
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
