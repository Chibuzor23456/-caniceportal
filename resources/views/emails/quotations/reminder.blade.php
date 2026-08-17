@component('mail::message')
# Quotation {{ $quotation->reference }} @if($daysRemaining <= 0) expires today @else expires in {{ $daysRemaining }} day{{ $daysRemaining === 1 ? '' : 's' }} @endif

Hi {{ $quotation->client->contact_person }},

Just a reminder that your quotation from Canice Technologies is still awaiting your decision.

@component('mail::button', ['url' => $secureUrl])
Review & Accept
@endcomponent

Once it expires you'll still be able to request a revision, but the quickest path is to review it now.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
