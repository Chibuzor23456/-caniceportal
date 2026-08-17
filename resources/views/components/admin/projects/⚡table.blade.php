<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $projects = Project::query()
            ->with('client', 'phases')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('client', fn ($q) => $q->where('company_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest('created_at')
            ->paginate(10);

        return ['projects' => $projects];
    }
};
?>

<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="$set('status', '')" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === '' ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">All</button>
            @foreach (ProjectStatus::cases() as $case)
                <button wire:click="$set('status', '{{ $case->value }}')" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === $case->value ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">
                    {{ $case->label() }}
                </button>
            @endforeach
        </div>

        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search projects&hellip;" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
    </div>

    <div class="mt-4 overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[680px] text-left text-sm">
            <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-3">Project</th>
                    <th class="px-5 py-3">Client</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Progress</th>
                    <th class="px-5 py-3">Expected Delivery</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" wire:loading.class="hidden" wire:target="search, status">
                @forelse ($projects as $project)
                    <tr wire:key="project-{{ $project->id }}">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.projects.show', $project) }}" class="font-medium text-slate-900 hover:text-brand">{{ $project->title }}</a>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $project->client->company_name }}</td>
                        <td class="px-5 py-3"><x-ui.pill :color="$project->status->pillColor()">{{ $project->status->label() }}</x-ui.pill></td>
                        <td class="px-5 py-3 text-slate-500">{{ $project->progressPercentage() }}%</td>
                        <td class="px-5 py-3 text-slate-500">{{ $project->expected_delivery_date?->format('M j, Y') ?? '-' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.projects.show', $project) }}" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">No projects match these filters yet.</td>
                    </tr>
                @endforelse
            </tbody>
            <tbody wire:loading wire:target="search, status">
                @for ($i = 0; $i < 5; $i++)
                    <tr>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-32" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-28" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-5 w-16 rounded-full" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-10" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-20" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="ml-auto h-4 w-12" /></td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $projects->links() }}
    </div>
</div>
