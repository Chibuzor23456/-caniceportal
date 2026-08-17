<?php

use App\Actions\Projects\AddPhaseCommentAction;
use App\Actions\Projects\ApprovePhaseAction;
use App\Actions\Projects\UploadDeliverableAction;
use App\Enums\PhaseStatus;
use App\Models\ProjectPhase;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ProjectPhase $phase;

    public string $role;

    public string $commentBody = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $files = [];

    public string $link = '';

    public string $deliverableNotes = '';

    public function mount(ProjectPhase $phase, string $role): void
    {
        $this->phase = $phase;
        $this->role = $role;
    }

    public function addComment(AddPhaseCommentAction $action): void
    {
        $this->validate(['commentBody' => 'required|string|max:5000']);

        $action->handle($this->phase, auth()->user(), $this->commentBody);

        $this->commentBody = '';
        $this->phase->refresh();
    }

    public function uploadDeliverable(UploadDeliverableAction $action): void
    {
        abort_unless($this->role === 'admin', 403);

        $this->validate([
            'files.*' => 'file|max:51200',
            'link' => 'nullable|url|max:255',
        ]);

        $action->handle($this->phase, auth()->user(), $this->files, $this->link ?: null, $this->deliverableNotes ?: null);

        $this->reset('files', 'link', 'deliverableNotes');
        $this->phase->refresh();
    }

    public function approve(ApprovePhaseAction $action): void
    {
        abort_unless($this->role === 'client', 403);

        $action->handle($this->phase);

        $this->phase->refresh();
    }

    public function with(): array
    {
        $this->phase->load('comments.author', 'deliverables.uploader', 'deliverables.files');

        $canApprove = $this->role === 'client'
            && $this->phase->isUnlocked()
            && in_array($this->phase->status, [PhaseStatus::PendingReview, PhaseStatus::InDiscussion], true);

        return [
            'unlocked' => $this->phase->isUnlocked(),
            'canApprove' => $canApprove,
            'canComment' => $this->phase->status !== PhaseStatus::Approved,
        ];
    }
};
?>

<div class="rounded-2xl bg-white p-6 shadow-sm" wire:key="phase-thread-{{ $phase->id }}">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-3">
            <h3 class="text-sm font-semibold text-slate-900">{{ $phase->name }}</h3>
            <x-ui.pill :color="$phase->status->pillColor()">{{ $phase->status->label() }}</x-ui.pill>
        </div>

        @if ($canApprove)
            <button type="button" wire:click="approve" wire:confirm="Approve this phase? The thread will lock once approved." class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Approve Phase</button>
        @endif
    </div>

    @if (! $unlocked)
        <p class="mt-4 text-sm text-slate-400">This phase unlocks once every earlier phase is approved.</p>
    @else
        {{-- Deliverables --}}
        <div class="mt-5 space-y-3">
            @forelse ($phase->deliverables as $deliverable)
                <div class="rounded-xl border border-slate-100 p-4">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Uploaded by {{ $deliverable->uploader->name }}</span>
                        <span>{{ $deliverable->created_at->diffForHumans() }}</span>
                    </div>

                    @if ($deliverable->notes)
                        <p class="mt-2 text-sm text-slate-700">{{ $deliverable->notes }}</p>
                    @endif

                    @if ($deliverable->link)
                        <a href="{{ $deliverable->link }}" target="_blank" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-brand hover:text-brand-emphasis">
                            View Link &rarr;
                        </a>
                    @endif

                    @if ($deliverable->files->isNotEmpty())
                        <ul class="mt-3 space-y-1">
                            @foreach ($deliverable->files as $file)
                                <li class="flex items-center gap-2 text-sm text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg>
                                    {{ $file->original_filename }}
                                    <span class="text-xs text-slate-400">({{ number_format($file->size / 1024, 1) }} KB)</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-400">No deliverables uploaded yet for this phase.</p>
            @endforelse
        </div>

        {{-- Admin upload form --}}
        @if ($role === 'admin')
            <form wire:submit="uploadDeliverable" class="mt-5 space-y-3 rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Upload Deliverable</p>

                <input type="file" wire:model="files" multiple class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-200 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-300">
                @error('files.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                <div wire:loading wire:target="files" class="text-xs text-slate-400">Uploading&hellip;</div>

                <input type="url" wire:model="link" placeholder="Link (optional, e.g. staging URL)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                @error('link') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                <textarea wire:model="deliverableNotes" rows="2" placeholder="Notes for the client (optional)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>

                <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">Upload</button>
            </form>
        @endif

        {{-- Comments --}}
        <div class="mt-6 border-t border-slate-100 pt-5">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Discussion</p>

            <div class="mt-3 space-y-4">
                @forelse ($phase->comments as $comment)
                    <div class="flex gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">
                            {{ substr($comment->author->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $comment->author->name }} <span class="ml-1 text-xs font-normal text-slate-400">{{ $comment->created_at->diffForHumans() }}</span></p>
                            <p class="mt-0.5 text-sm text-slate-700">{{ $comment->body }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No comments yet.</p>
                @endforelse
            </div>

            @if ($canComment)
                <form wire:submit="addComment" class="mt-4 flex gap-2">
                    <input type="text" wire:model="commentBody" placeholder="Write a comment&hellip;" class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Send</button>
                </form>
                @error('commentBody') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @else
                <p class="mt-4 text-xs text-slate-400">This phase is approved - the thread is locked.</p>
            @endif
        </div>
    @endif
</div>
