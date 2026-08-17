<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
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
        $invoices = Invoice::query()
            ->with('client')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                    ->orWhereHas('client', fn ($q) => $q->where('company_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest('created_at')
            ->paginate(10);

        return ['invoices' => $invoices];
    }
};
?>

<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="$set('status', '')" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === '' ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">All</button>
            @foreach (InvoiceStatus::cases() as $case)
                <button wire:click="$set('status', '{{ $case->value }}')" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $status === $case->value ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">
                    {{ $case->label() }}
                </button>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search invoices&hellip;" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <a href="{{ route('admin.invoices.export') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Export CSV</a>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[680px] text-left text-sm">
            <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-3">Reference</th>
                    <th class="px-5 py-3">Client</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Amount</th>
                    <th class="px-5 py-3">Due</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50" wire:loading.class="hidden" wire:target="search, status">
                @forelse ($invoices as $invoice)
                    <tr wire:key="invoice-{{ $invoice->id }}">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="font-medium text-slate-900 hover:text-brand">{{ $invoice->reference }}</a>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $invoice->client->company_name }}</td>
                        <td class="px-5 py-3"><x-ui.pill :color="$invoice->status->pillColor()">{{ $invoice->status->label() }}</x-ui.pill></td>
                        <td class="px-5 py-3 text-slate-500">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $invoice->due_date?->format('M j, Y') ?? '-' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">No invoices match these filters yet.</td>
                    </tr>
                @endforelse
            </tbody>
            <tbody wire:loading wire:target="search, status">
                @for ($i = 0; $i < 5; $i++)
                    <tr>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-24" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-32" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-5 w-16 rounded-full" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-20" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="h-4 w-16" /></td>
                        <td class="px-5 py-3"><x-ui.skeleton class="ml-auto h-4 w-12" /></td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
</div>
