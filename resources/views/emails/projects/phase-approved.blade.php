@component('mail::message')
# Phase Approved

**{{ $phase->project->client->company_name }}** approved phase **{{ $phase->name }}** on **{{ $phase->project->title }}**.

@component('mail::button', ['url' => $url])
View Project
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
