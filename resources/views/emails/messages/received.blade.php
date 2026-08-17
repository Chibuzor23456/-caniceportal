@component('mail::message')
# New Message

@if ($forAdmin)
**{{ $message->client->company_name }}** sent a new message:
@else
Canice Technologies sent you a new message:
@endif

@if ($message->body)
> {{ $message->body }}
@else
*(attachment only)*
@endif

@component('mail::button', ['url' => $url])
View & Reply
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
