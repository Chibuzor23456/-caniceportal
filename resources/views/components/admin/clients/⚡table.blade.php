<?php

use App\Enums\ClientStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Tag;
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

    #[Url]
    public string $tag = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function suspend(int $clientId): void
    {
        $client = Client::findOrFail($clientId);
        $client->update(['status' => ClientStatus::Suspended]);

        ActivityLog::record('client.suspended', "Client \"{$client->company_name}\" was suspended.", $client);
    }

    public function reactivate(int $clientId): void
    {
        $client = Client::findOrFail($clientId);
        $client->update(['status' => ClientStatus::Active]);

        ActivityLog::record('client.reactivated', "Client \"{$client->company_name}\" was reactivated.", $client);
    }

    public function delete(int $clientId): void
    {
        $client = Client::findOrFail($clientId);
        $name = $client->company_name;
        $client->delete();

        ActivityLog::record('client.deleted', "Client \"{$name}\" was deleted.");
    }

    public function with(): array
    {
        $clients = Client::query()
            ->with('tags')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('company_name', 'like', "%{$this->search}%")
                    ->orWhere('contact_person', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->tag, fn ($q) => $q->whereHas('tags', fn ($q) => $q->where('tags.id', $this->tag)))
            ->latest('date_joined')
            ->paginate(10);

        return [
            'clients' => $clients,
            'tags' => Tag::orderBy('name')->get(),
        ];
    }
};
?>

<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="$set('status', '')" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === '' ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">All</button>
            @foreach (\App\Enums\ClientStatus::cases() as $case)
                <button wire:click="$set('status', '{{ $case->value }}')" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === $case->value ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">
                    {{ $case->label() }}
                </button>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <select wire:model.live="tag" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
                <option value="">All tags</option>
                @foreach ($tags as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search clients&hellip;" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <a href="{{ route('admin.clients.create') }}" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">+ New Client</a>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-3">Company</th>
                    <th class="px-5 py-3">Contact</th>
                    <th class="px-5 py-3">Tags</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Joined</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" wire:loading.class="hidden" wire:target="search, status, tag">
                @forelse ($clients as $client)
                    <tr wire:key="client-{{ $client->id }}">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.clients.show', $client) }}" class="font-medium text-slate-900 hover:text-brand">{{ $client->company_name }}</a>
                        </td>
                        <td class="px-5 py-3 text-slate-500">
                            {{ $client->contact_person }}<br>
                            <span class="text-xs text-slate-400">{{ $client->email }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($client->tags as $t)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ $t->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <x-ui.pill :color="$client->status->pillColor()">{{ $client->status->label() }}</x-ui.pill>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $client->date_joined->format('M j, Y') }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-2 text-xs">
                                <a href="{{ route('admin.clients.edit', $client) }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 font-medium text-slate-600 hover:bg-slate-50">Edit</a>
                                @if ($client->status === ClientStatus::Suspended)
                                    <button wire:click="reactivate({{ $client->id }})" class="rounded-lg border border-emerald-200 px-2.5 py-1.5 font-medium text-emerald-600 hover:bg-emerald-50">Reactivate</button>
                                @else
                                    <button wire:click="suspend({{ $client->id }})" wire:confirm="Suspend this client? They'll be immediately unable to log in." class="rounded-lg border border-orange-200 px-2.5 py-1.5 font-medium text-orange-600 hover:bg-orange-50">Suspend</button>
                                @endif
                                <button wire:click="delete({{ $client->id }})" wire:confirm="Delete this client? This can't be undone from here." class="rounded-lg bg-red-50 px-2.5 py-1.5 font-medium text-red-600 hover:bg-red-100">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">No clients match these filters yet.</td>
                    </tr>
                @endforelse
            </tbody>
            <tbody wire:loading wire:target="search, status, tag">
                @for ($i = 0; $i < 5; $i++)
                    <tr>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-32" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-40" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-20" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-5 w-16 rounded-full" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-16" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="ml-auto h-4 w-12" /></td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $clients->links() }}
    </div>
</div>
