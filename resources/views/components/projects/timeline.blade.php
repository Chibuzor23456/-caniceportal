@props(['project'])
@php
    $phases = $project->phases;
@endphp
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <h3 class="text-sm font-semibold text-slate-900">Timeline</h3>

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-start">
        @forelse ($phases as $phase)
            @php
                $isApproved = $phase->status === \App\Enums\PhaseStatus::Approved;
                $isUnlocked = $phase->isUnlocked();
                $state = $isApproved ? 'completed' : ($isUnlocked ? 'current' : 'upcoming');
                $circleClass = match ($state) {
                    'completed' => 'bg-emerald-500 text-white',
                    'current' => 'bg-brand text-white',
                    default => 'bg-slate-100 text-slate-400',
                };
            @endphp
            <div class="flex flex-1 items-start gap-3 sm:flex-col sm:items-center sm:text-center">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $circleClass }}">
                    @if ($isApproved)
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="h-4 w-4"><path d="M20 6 9 17l-5-5"/></svg>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </div>
                <div class="sm:mt-2">
                    <p class="text-sm font-medium {{ $state === 'upcoming' ? 'text-slate-400' : 'text-slate-900' }}">{{ $phase->name }}</p>
                    <x-ui.pill :color="$phase->status->pillColor()" class="mt-1">{{ $phase->status->label() }}</x-ui.pill>
                </div>
            </div>

            @if (! $loop->last)
                <div class="ml-4 h-6 w-px bg-slate-100 sm:mt-4 sm:ml-0 sm:h-px sm:w-full sm:flex-1"></div>
            @endif
        @empty
            <p class="text-sm text-slate-400">No phases added yet.</p>
        @endforelse
    </div>
</div>
