@component('mail::message')
# Quotation Declined

**{{ $quotation->client->company_name }}** declined quotation **{{ $quotation->reference }}**.

@if ($quotation->rejection_reason)
Their stated reason:

> {{ $quotation->rejection_reason }}
@endif

@component('mail::button', ['url' => $adminUrl])
View in Admin
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
