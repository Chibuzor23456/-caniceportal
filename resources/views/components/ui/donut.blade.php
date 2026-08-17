@props(['segments', 'centerLabel' => null, 'centerValue' => null])
@php
    $total = collect($segments)->sum('value');
    $stops = [];
    $cursor = 0;

    foreach ($segments as $segment) {
        $share = $total > 0 ? ($segment['value'] / $total) * 100 : 0;
        $start = $cursor;
        $end = $cursor + $share;
        $stops[] = "{$segment['color']} {$start}% {$end}%";
        $cursor = $end;
    }

    $gradient = $total > 0 ? implode(', ', $stops) : '#e2e8f0 0% 100%';
@endphp
<div class="flex items-center gap-5">
    <div class="relative h-28 w-28 shrink-0 rounded-full" style="background: conic-gradient({{ $gradient }});">
        <div class="absolute inset-2.5 flex flex-col items-center justify-center rounded-full bg-white text-center">
            @if (! is_null($centerValue))
                <span class="text-lg font-bold text-slate-900">{{ $centerValue }}</span>
            @endif
            @if ($centerLabel)
                <span class="text-[10px] text-slate-400">{{ $centerLabel }}</span>
            @endif
        </div>
    </div>
    <ul class="space-y-1.5 text-xs">
        @foreach ($segments as $segment)
            <li class="flex items-center gap-2">
                <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $segment['color'] }};"></span>
                <span class="text-slate-500">{{ $segment['label'] }}</span>
                <span class="font-medium text-slate-700">{{ $segment['value'] }}</span>
            </li>
        @endforeach
    </ul>
</div>
