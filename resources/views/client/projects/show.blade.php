<x-layouts.client :page-title="$project->title" :title="$project->title">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">{{ $project->title }}</h2>
                    <x-ui.pill :color="$project->status->pillColor()">{{ $project->status->label() }}</x-ui.pill>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    From {{ $project->quotation->reference }}
                    @if ($project->expected_delivery_date)
                        &middot; Expected {{ $project->expected_delivery_date->format('M j, Y') }}
                    @endif
                    @if ($project->completion_date)
                        &middot; Completed {{ $project->completion_date->format('M j, Y') }}
                    @endif
                </p>
            </div>

            <div class="text-right">
                <p class="text-2xl font-bold text-slate-900">{{ $project->progressPercentage() }}%</p>
                <p class="text-xs text-slate-400">Progress</p>
            </div>
        </div>

        @if ($project->description)
            <p class="mt-4 text-sm text-slate-700">{{ $project->description }}</p>
        @endif
    </div>

    <div class="mt-6">
        <x-projects.timeline :project="$project" />
    </div>

    <div class="mt-6 space-y-6">
        @foreach ($project->phases as $phase)
            <livewire:projects.phase-thread :phase="$phase" role="client" :key="'phase-thread-'.$phase->id" />
        @endforeach
    </div>
</x-layouts.client>
