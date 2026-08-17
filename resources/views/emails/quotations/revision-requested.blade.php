@component('mail::message')
# Revision Requested

**{{ $quotation->client->company_name }}** requested a revision on expired quotation **{{ $quotation->reference }}**.

@component('mail::button', ['url' => $adminUrl])
View in Admin
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
