@component('mail::message')
# Payment Proof Uploaded

**{{ $invoice->client->company_name }}** uploaded proof of payment for invoice **{{ $invoice->reference }}** ({{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}).

@component('mail::button', ['url' => $url])
Review & Verify
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
