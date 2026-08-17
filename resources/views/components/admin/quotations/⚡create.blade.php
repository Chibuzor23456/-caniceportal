<?php

use App\Actions\Quotations\CreateQuotationAction;
use App\Models\Client;
use App\Models\QuotationTemplate;
use Livewire\Component;

new class extends Component
{
    public ?int $clientId = null;

    public function selectClient(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function createBlank(CreateQuotationAction $action)
    {
        $quotation = $action->handle(Client::findOrFail($this->clientId));

        return $this->redirect(route('admin.quotations.edit', $quotation), navigate: false);
    }

    public function createFromTemplate(int $templateId, CreateQuotationAction $action)
    {
        $quotation = $action->handle(
            Client::findOrFail($this->clientId),
            QuotationTemplate::findOrFail($templateId),
        );

        return $this->redirect(route('admin.quotations.edit', $quotation), navigate: false);
    }

    public function with(): array
    {
        $client = $this->clientId ? Client::find($this->clientId) : null;

        $suggested = collect();
        $all = collect();

        if ($client) {
            $clientTagIds = $client->tags->pluck('id');

            $all = QuotationTemplate::with('tags')->latest()->get();

            $suggested = $clientTagIds->isEmpty()
                ? collect()
                : $all->filter(fn (QuotationTemplate $t) => $t->tags->pluck('id')->intersect($clientTagIds)->isNotEmpty());
        }

        return [
            'clients' => Client::orderBy('company_name')->get(),
            'selectedClient' => $client,
            'suggestedTemplates' => $suggested,
            'allTemplates' => $all,
        ];
    }
};
?>

<div class="w-full">
    @if (! $selectedClient)
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Who is this quotation for?</h2>
            <div class="mt-4 grid grid-cols-1 gap-1 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($clients as $client)
                    <button wire:click="selectClient({{ $client->id }})" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm hover:bg-slate-50">
                        <span>
                            <span class="font-medium text-slate-900">{{ $client->company_name }}</span>
                            <span class="text-slate-400"> &middot; {{ $client->contact_person }}</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-slate-300"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                @endforeach
            </div>
        </div>
    @else
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-slate-500">Building for <strong class="text-slate-900">{{ $selectedClient->company_name }}</strong></p>
            <button wire:click="$set('clientId', null)" class="text-xs font-medium text-slate-400 hover:text-slate-600">Change client</button>
        </div>

        @if ($suggestedTemplates->isNotEmpty())
            <h2 class="text-sm font-semibold text-slate-900">Suggested for this client</h2>
            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($suggestedTemplates as $template)
                    <button wire:click="createFromTemplate({{ $template->id }})" class="rounded-2xl border-2 border-brand/20 bg-white p-4 text-left shadow-sm hover:border-brand">
                        <p class="font-medium text-slate-900">{{ $template->name }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $template->tags->pluck('name')->join(', ') }}</p>
                    </button>
                @endforeach
            </div>
        @endif

        <h2 class="mt-6 text-sm font-semibold text-slate-900">All Templates</h2>
        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($allTemplates as $template)
                <button wire:click="createFromTemplate({{ $template->id }})" class="rounded-2xl bg-white p-4 text-left shadow-sm hover:ring-2 hover:ring-brand/30">
                    <p class="font-medium text-slate-900">{{ $template->name }}</p>
                </button>
            @empty
                <p class="text-sm text-slate-400">No templates saved yet.</p>
            @endforelse
        </div>

        <button wire:click="createBlank" class="mt-6 w-full rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm font-medium text-slate-500 hover:bg-slate-50">
            Start from a blank quotation
        </button>
    @endif
</div>
