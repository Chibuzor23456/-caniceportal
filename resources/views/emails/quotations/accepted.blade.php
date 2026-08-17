@component('mail::message')
# Quotation Accepted

@if ($forAdmin)
**{{ $quotation->client->company_name }}** just accepted quotation **{{ $quotation->reference }}**. A project has been created automatically.
@else
Thanks for accepting quotation **{{ $quotation->reference }}**. Your signed copy is available any time in your portal.
@endif

@component('mail::button', ['url' => $url])
View Signed Quotation
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
