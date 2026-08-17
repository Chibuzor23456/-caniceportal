@component('mail::message')
# Project Completed

@if ($forAdmin)
**{{ $project->title }}** has been marked completed - every phase was approved by **{{ $project->client->company_name }}**.
@else
All phases of **{{ $project->title }}** have been approved. Your project is now complete.
@endif

@component('mail::button', ['url' => $url])
View Project
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
