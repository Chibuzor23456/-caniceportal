@component('mail::message')
# Quotation Viewed

**{{ $quotation->client->company_name }}** just opened quotation **{{ $quotation->reference }}**.

@component('mail::button', ['url' => $adminUrl])
View in Admin
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
