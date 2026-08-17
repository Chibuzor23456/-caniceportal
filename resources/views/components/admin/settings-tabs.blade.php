@props(['active'])
@php
    $tabs = [
        'company' => ['label' => 'Company', 'route' => 'admin.settings.company'],
        'smtp' => ['label' => 'SMTP', 'route' => 'admin.settings.smtp'],
        'notifications' => ['label' => 'Notifications', 'route' => 'admin.settings.notifications'],
        'email-templates' => ['label' => 'Email Templates', 'route' => 'admin.settings.email-templates.index'],
    ];
@endphp

<div class="mb-6 flex flex-wrap gap-2 border-b border-slate-100 pb-2">
    @foreach ($tabs as $key => $tab)
        <a href="{{ route($tab['route']) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $active === $key ? 'bg-brand text-white' : 'text-slate-500 hover:bg-slate-50' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
