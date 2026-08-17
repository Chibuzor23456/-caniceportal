@component('mail::message')
# New Deliverable Uploaded

A new deliverable is ready for your review on **{{ $phase->project->title }}**, phase **{{ $phase->name }}**.

@if ($deliverable->notes)
{{ $deliverable->notes }}
@endif

@component('mail::button', ['url' => $url])
Review Deliverable
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
