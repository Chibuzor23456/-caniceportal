@component('mail::message')
# New Invoice

You have a new invoice from Canice Technologies.

**Invoice:** {{ $invoice->reference }}<br>
**Amount:** {{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}<br>
@if ($invoice->due_date)
**Due:** {{ $invoice->due_date->format('M j, Y') }}
@endif

@component('mail::button', ['url' => $url])
View Invoice
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
