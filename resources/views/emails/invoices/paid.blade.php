@component('mail::message')
# Payment Received

We've confirmed your payment for invoice **{{ $invoice->reference }}** ({{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}). Thank you.

@component('mail::button', ['url' => $url])
View Invoice
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
