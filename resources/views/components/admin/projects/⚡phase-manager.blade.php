<?php

use App\Enums\PhaseStatus;
use App\Models\ActivityLog;
use App\Models\Project;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    public string $newPhaseName = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function addPhase(): void
    {
        $this->validate(['newPhaseName' => 'required|string|max:255']);

        $nextOrder = ($this->project->phases()->max('order') ?? -1) + 1;

        $phase = $this->project->phases()->create([
            'name' => $this->newPhaseName,
            'order' => $nextOrder,
            'status' => PhaseStatus::NotStarted,
        ]);

        ActivityLog::record(
            action: 'project.phase_added',
            description: "Phase \"{$phase->name}\" was added to \"{$this->project->title}\".",
            subject: $phase,
        );

        $this->newPhaseName = '';
        $this->project->unsetRelation('phases');
    }

    public function deletePhase(int $phaseId): void
    {
        $phase = $this->project->phases()->findOrFail($phaseId);

        abort_unless($phase->status === PhaseStatus::NotStarted, 422, 'Only untouched phases can be removed.');

        $phase->delete();

        $this->project->unsetRelation('phases');
    }

    public function reorderPhase($key, $position): void
    {
        $phases = $this->project->phases()->orderBy('order')->get();
        $moving = $phases->firstWhere('id', (int) $key);

        if (! $moving) {
            return;
        }

        $rest = $phases->reject(fn ($p) => $p->id === $moving->id)->values();
        $rest->splice($position, 0, [$moving]);

        foreach ($rest as $index => $phase) {
            $phase->update(['order' => $index]);
        }

        $this->project->unsetRelation('phases');
    }

    public function with(): array
    {
        return ['phases' => $this->project->phases()->orderBy('order')->get()];
    }
};
?>

<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-900">Manage Phases</h3>
    </div>

    <div x-sort="$wire.reorderPhase($item, $position)" class="mt-4 space-y-2">
        @forelse ($phases as $phase)
            <div x-sort:item="{{ $phase->id }}" wire:key="phase-manager-{{ $phase->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-4 py-3">
                <div class="flex items-center gap-3">
                    <span x-sort:handle class="cursor-move text-slate-300 hover:text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><circle cx="9" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                    </span>
                    <span class="text-sm font-medium text-slate-900">{{ $phase->name }}</span>
                    <x-ui.pill :color="$phase->status->pillColor()">{{ $phase->status->label() }}</x-ui.pill>
                </div>

                @if ($phase->status === \App\Enums\PhaseStatus::NotStarted)
                    <button type="button" wire:click="deletePhase({{ $phase->id }})" wire:confirm="Remove this phase?" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">Remove</button>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-400">No phases added yet. Add the first one below.</p>
        @endforelse
    </div>

    <form wire:submit="addPhase" class="mt-4 flex flex-col gap-2 sm:flex-row">
        <input type="text" wire:model="newPhaseName" placeholder="e.g. Design, Development, Testing&hellip;" class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">+ Add Phase</button>
    </form>
    @error('newPhaseName')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
