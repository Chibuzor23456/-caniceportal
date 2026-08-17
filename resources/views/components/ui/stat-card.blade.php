@props([
    'label',
    'value',
    'color' => 'slate',
    'delta' => null,
    'zeroState' => false,
])
@php
    $colors = [
        'blue' => 'bg-blue-50 text-blue-600',
        'orange' => 'bg-orange-50 text-orange-600',
        'green' => 'bg-emerald-50 text-emerald-600',
        'purple' => 'bg-purple-50 text-purple-600',
        'slate' => 'bg-slate-100 text-slate-500',
    ];
@endphp
<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $colors[$color] ?? $colors['slate'] }}">
        {{ $icon ?? '' }}
    </div>
    <p class="mt-4 text-sm text-slate-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $zeroState ? '-' : $value }}</p>

    @if ($zeroState)
        <p class="mt-1 text-xs text-slate-400">Coming in a later phase</p>
    @elseif (! is_null($delta))
        <p class="mt-1 flex items-center gap-1 text-xs font-medium {{ $delta >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
            <span>{{ $delta >= 0 ? '↑' : '↓' }}</span>
            <span>{{ abs($delta) }}% vs last week</span>
        </p>
    @endif
</div>
