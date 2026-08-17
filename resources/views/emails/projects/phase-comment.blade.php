@component('mail::message')
# New Comment

**{{ $comment->author->name }}** commented on **{{ $phase->project->title }}**, phase **{{ $phase->name }}**:

> {{ $comment->body }}

@component('mail::button', ['url' => $url])
View & Reply
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
