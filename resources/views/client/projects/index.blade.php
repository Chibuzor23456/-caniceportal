<x-layouts.client page-title="My Projects" title="My Projects">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
        @forelse ($projects as $project)
            <a href="{{ route('client.projects.show', $project) }}" class="rounded-2xl bg-white p-5 shadow-sm hover:shadow-md">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-semibold text-slate-900">{{ $project->title }}</h3>
                    <x-ui.pill :color="$project->status->pillColor()">{{ $project->status->label() }}</x-ui.pill>
                </div>

                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Progress</span>
                        <span>{{ $project->progressPercentage() }}%</span>
                    </div>
                    <div class="mt-1 h-2 w-full rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-brand" style="width: {{ $project->progressPercentage() }}%"></div>
                    </div>
                </div>

                @if ($project->expected_delivery_date)
                    <p class="mt-3 text-xs text-slate-400">Expected {{ $project->expected_delivery_date->format('M j, Y') }}</p>
                @endif
            </a>
        @empty
            <div class="col-span-full rounded-2xl bg-white p-12 text-center shadow-sm">
                <p class="text-sm text-slate-400">No projects yet. This fills in once your first quotation is accepted.</p>
            </div>
        @endforelse
    </div>
</x-layouts.client>
