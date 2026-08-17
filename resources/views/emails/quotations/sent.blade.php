@component('mail::message')
# Your Quotation is Ready

Hi {{ $quotation->client->contact_person }},

A new quotation from Canice Technologies is ready for your review: **{{ $quotation->reference }}**.

@component('mail::button', ['url' => $secureUrl])
View Quotation
@endcomponent

This link is valid until {{ $quotation->expiry_date->format('F j, Y') }}. No login is required to view it.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
