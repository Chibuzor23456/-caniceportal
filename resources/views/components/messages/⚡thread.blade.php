<?php

use App\Actions\Messages\SendMessageAction;
use App\Models\Client;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Client $client;

    public string $role;

    public string $body = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $files = [];

    public function mount(Client $client, string $role): void
    {
        $this->authorize('view', $client);

        $this->client = $client;
        $this->role = $role;
    }

    public function send(SendMessageAction $action): void
    {
        $this->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'files.*' => ['file', 'max:10240'],
        ]);

        if ($this->body === '' && empty($this->files)) {
            $this->addError('body', 'Write a message or attach a file.');

            return;
        }

        $action->handle($this->client, auth()->user(), $this->body ?: null, $this->files);

        $this->reset('body', 'files');
    }

    private function markRead(): void
    {
        $otherRole = $this->role === 'admin' ? 'client' : 'admin';

        $this->client->messages()
            ->whereNull('read_at')
            ->whereHas('sender', fn ($q) => $q->where('role', $otherRole))
            ->update(['read_at' => now()]);
    }

    public function with(): array
    {
        $this->markRead();

        return [
            'messages' => $this->client->messages()->with('sender', 'attachments')->orderBy('created_at')->get(),
        ];
    }
};
?>

<div wire:poll.10s class="flex h-[32rem] flex-col rounded-2xl bg-white shadow-sm">
    <div class="flex-1 space-y-4 overflow-y-auto p-6">
        @forelse ($messages as $message)
            @php $isMine = $message->sender_id === auth()->id(); @endphp
            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%] rounded-2xl px-4 py-2.5 {{ $isMine ? 'bg-brand text-white' : 'bg-slate-100 text-slate-900' }}">
                    @if ($message->body)
                        <p class="text-sm whitespace-pre-line">{{ $message->body }}</p>
                    @endif

                    @if ($message->attachments->isNotEmpty())
                        <ul class="mt-1 space-y-1 {{ $message->body ? 'border-t pt-1 '.($isMine ? 'border-white/20' : 'border-slate-200') : '' }}">
                            @foreach ($message->attachments as $attachment)
                                <li class="flex items-center gap-1.5 text-xs {{ $isMine ? 'text-white/90' : 'text-slate-600' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5 shrink-0"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg>
                                    {{ $attachment->original_filename }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <p class="mt-1 text-[10px] {{ $isMine ? 'text-white/70' : 'text-slate-400' }}">
                        {{ $message->sender->name }} &middot; {{ $message->created_at->diffForHumans() }}
                        @if ($isMine && $message->read_at) &middot; Read @endif
                    </p>
                </div>
            </div>
        @empty
            <p class="mt-8 text-center text-sm text-slate-400">No messages yet. Say hello.</p>
        @endforelse
    </div>

    <form wire:submit="send" class="border-t border-slate-100 p-4">
        <div class="flex items-end gap-2">
            <textarea wire:model="body" rows="2" placeholder="Write a message&hellip;" class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
            <label class="cursor-pointer rounded-xl border border-slate-200 p-2.5 text-slate-400 hover:bg-slate-50">
                <input type="file" wire:model="files" multiple class="hidden">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            </label>
            <button type="submit" class="rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">Send</button>
        </div>

        @if (! empty($files))
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($files as $file)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $file->getClientOriginalName() }}</span>
                @endforeach
            </div>
        @endif

        @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        @error('files.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </form>
</div>
