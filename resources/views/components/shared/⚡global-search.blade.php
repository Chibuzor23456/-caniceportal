<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $query = '';

    public function with(): array
    {
        $groups = [];
        $user = Auth::user();
        $term = trim($this->query);

        if (! $user || strlen($term) < 2) {
            return ['groups' => $groups];
        }

        if ($user->isAdmin()) {
            $groups['Clients'] = Client::query()
                ->where(function ($q) use ($term) {
                    $q->where('company_name', 'like', "%{$term}%")
                        ->orWhere('contact_person', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                })
                ->limit(5)
                ->get()
                ->map(fn (Client $c) => [
                    'title' => $c->company_name,
                    'subtitle' => $c->contact_person,
                    'url' => route('admin.clients.show', $c),
                ]);

            $projects = Project::with('client')
                ->where('title', 'like', "%{$term}%")
                ->orWhereHas('client', fn ($q) => $q->where('company_name', 'like', "%{$term}%"))
                ->limit(5)->get();

            $quotations = Quotation::with('client')
                ->where('reference', 'like', "%{$term}%")
                ->orWhereHas('client', fn ($q) => $q->where('company_name', 'like', "%{$term}%"))
                ->limit(5)->get();

            $invoices = Invoice::with('client')
                ->where('reference', 'like', "%{$term}%")
                ->orWhereHas('client', fn ($q) => $q->where('company_name', 'like', "%{$term}%"))
                ->limit(5)->get();

            $groups['Projects'] = $projects->map(fn (Project $p) => [
                'title' => $p->title, 'subtitle' => $p->client->company_name, 'url' => route('admin.projects.show', $p),
            ]);
            $groups['Quotations'] = $quotations->map(fn (Quotation $q) => [
                'title' => $q->reference, 'subtitle' => $q->client->company_name, 'url' => route('admin.quotations.show', $q),
            ]);
            $groups['Invoices'] = $invoices->map(fn (Invoice $i) => [
                'title' => $i->reference, 'subtitle' => $i->client->company_name, 'url' => route('admin.invoices.show', $i),
            ]);
        } elseif ($user->isClient() && $client = $user->client) {
            $groups['Projects'] = Project::where('client_id', $client->id)
                ->where('title', 'like', "%{$term}%")
                ->limit(5)->get()
                ->map(fn (Project $p) => ['title' => $p->title, 'subtitle' => $p->status->label(), 'url' => route('client.projects.show', $p)]);

            $groups['Quotations'] = Quotation::where('client_id', $client->id)
                ->where('reference', 'like', "%{$term}%")
                ->limit(5)->get()
                ->map(fn (Quotation $q) => ['title' => $q->reference, 'subtitle' => $q->status->label(), 'url' => route('client.quotations.show', $q)]);

            $groups['Invoices'] = Invoice::where('client_id', $client->id)
                ->where('reference', 'like', "%{$term}%")
                ->limit(5)->get()
                ->map(fn (Invoice $i) => ['title' => $i->reference, 'subtitle' => $i->status->label(), 'url' => route('client.invoices.show', $i)]);
        }

        return ['groups' => collect($groups)->reject(fn ($g) => $g->isEmpty())->all()];
    }
};
?>

<div class="overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="searchOpen = false">
    <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5 text-slate-300">
            <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
        </svg>
        <input
            type="text"
            wire:model.live.debounce.200ms="query"
            placeholder="Search&hellip;"
            autofocus
            class="w-full border-0 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-0"
        >
        <kbd class="rounded-md border border-slate-200 px-1.5 py-0.5 text-[10px] font-medium text-slate-400">Esc</kbd>
    </div>

    <div class="max-h-96 overflow-y-auto p-2" wire:loading.class="hidden" wire:target="query">
        @if (strlen(trim($query)) < 2)
            <p class="px-3 py-6 text-center text-sm text-slate-400">Type at least 2 characters to search.</p>
        @elseif (empty($groups))
            <p class="px-3 py-6 text-center text-sm text-slate-400">No results for &ldquo;{{ $query }}&rdquo;.</p>
        @else
            @foreach ($groups as $label => $results)
                <p class="px-3 pt-2 pb-1 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $label }}</p>
                @foreach ($results as $result)
                    <a
                        href="{{ $result['url'] }}"
                        class="flex items-center justify-between rounded-xl px-3 py-2 text-sm hover:bg-slate-50"
                    >
                        <span class="font-medium text-slate-900">{{ $result['title'] }}</span>
                        <span class="text-xs text-slate-400">{{ $result['subtitle'] }}</span>
                    </a>
                @endforeach
            @endforeach
        @endif
    </div>

    <div class="space-y-2 p-3" wire:loading wire:target="query">
        <x-ui.skeleton class="h-3 w-16" />
        <x-ui.skeleton class="h-10 w-full" />
        <x-ui.skeleton class="h-10 w-full" />
        <x-ui.skeleton class="h-10 w-full" />
    </div>
</div>
