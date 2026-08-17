<?php

use App\Enums\SectionType;
use App\Models\Quotation;
use App\Models\QuotationTemplate;
use App\Models\QuotationVersion;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public Quotation $quotation;

    public array $sections = [];

    public array $lineItems = [];

    public array $paymentPhases = [];

    public string $currency = 'NGN';

    public bool $showTemplateModal = false;

    public string $templateName = '';

    public function mount(Quotation $quotation): void
    {
        $this->quotation = $quotation;
        $this->currency = $quotation->currency;

        $this->sections = $quotation->sections->map(fn ($s) => [
            'uid' => (string) $s->id,
            'type' => $s->type->value,
            'title' => $s->title,
            'body' => $s->body,
        ])->all();

        $this->lineItems = $quotation->lineItems->map(fn ($i) => [
            'uid' => (string) $i->id,
            'service_name' => $i->service_name,
            'service_category' => $i->service_category,
            'quantity' => (float) $i->quantity,
            'unit_price' => (float) $i->unit_price,
            'discount' => (float) $i->discount,
            'tax' => (float) $i->tax,
        ])->all();

        $this->paymentPhases = $quotation->paymentPhases->map(fn ($p) => [
            'uid' => (string) $p->id,
            'description' => $p->description,
            'amount' => (float) $p->amount,
            'due_condition' => $p->due_condition,
        ])->all();
    }

    public function addSection(string $type): void
    {
        if ($type === SectionType::PricingTable->value && collect($this->sections)->contains('type', SectionType::PricingTable->value)) {
            return;
        }

        if ($type === SectionType::PaymentSchedule->value && collect($this->sections)->contains('type', SectionType::PaymentSchedule->value)) {
            return;
        }

        $this->sections[] = [
            'uid' => 'new-'.Str::random(8),
            'type' => $type,
            'title' => SectionType::from($type)->label(),
            'body' => '',
        ];

        if ($type === SectionType::PricingTable->value && empty($this->lineItems)) {
            $this->addLineItem();
        }

        if ($type === SectionType::PaymentSchedule->value && empty($this->paymentPhases)) {
            $this->addPaymentPhase();
        }
    }

    public function removeSection(string $uid): void
    {
        $this->sections = collect($this->sections)->reject(fn ($s) => $s['uid'] === $uid)->values()->all();
    }

    public function duplicateSection(string $uid): void
    {
        $section = collect($this->sections)->firstWhere('uid', $uid);

        if (! $section || in_array($section['type'], [SectionType::PricingTable->value, SectionType::PaymentSchedule->value], true)) {
            return;
        }

        $section['uid'] = 'new-'.Str::random(8);
        $section['title'] .= ' (Copy)';
        $this->sections[] = $section;
    }

    public function reorderSection($key, $position): void
    {
        $items = collect($this->sections);
        $moving = $items->firstWhere('uid', (string) $key);

        if (! $moving) {
            return;
        }

        $rest = $items->reject(fn ($s) => $s['uid'] === (string) $key)->values();
        $rest->splice($position, 0, [$moving]);

        $this->sections = $rest->all();
    }

    public function addLineItem(): void
    {
        $this->lineItems[] = [
            'uid' => 'new-'.Str::random(8),
            'service_name' => '',
            'service_category' => null,
            'quantity' => 1,
            'unit_price' => 0,
            'discount' => 0,
            'tax' => 0,
        ];
    }

    public function removeLineItem(string $uid): void
    {
        $this->lineItems = collect($this->lineItems)->reject(fn ($i) => $i['uid'] === $uid)->values()->all();
    }

    public function lineTotal(array $item): float
    {
        return round(((float) $item['quantity'] * (float) $item['unit_price']) - (float) $item['discount'] + (float) $item['tax'], 2);
    }

    public function grandTotal(): float
    {
        return round(collect($this->lineItems)->sum(fn ($i) => $this->lineTotal($i)), 2);
    }

    public function addPaymentPhase(): void
    {
        $this->paymentPhases[] = [
            'uid' => 'new-'.Str::random(8),
            'description' => '',
            'amount' => 0,
            'due_condition' => '',
        ];
    }

    public function removePaymentPhase(string $uid): void
    {
        $this->paymentPhases = collect($this->paymentPhases)->reject(fn ($p) => $p['uid'] === $uid)->values()->all();
    }

    public function paymentPhasesTotal(): float
    {
        return round(collect($this->paymentPhases)->sum(fn ($p) => (float) $p['amount']), 2);
    }

    public function save(): void
    {
        $this->validate([
            'sections.*.title' => ['required', 'string', 'max:255'],
            'lineItems.*.service_name' => ['nullable', 'string', 'max:255'],
            'paymentPhases.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $wasSent = $this->quotation->status->value !== 'draft';

        if ($wasSent) {
            $this->quotation->versions()->create([
                'version_number' => $this->quotation->version,
                'snapshot' => [
                    'sections' => $this->quotation->sections->toArray(),
                    'lineItems' => $this->quotation->lineItems->toArray(),
                    'paymentPhases' => $this->quotation->paymentPhases->toArray(),
                ],
            ]);
        }

        $this->quotation->sections()->delete();
        foreach ($this->sections as $order => $section) {
            $this->quotation->sections()->create([
                'type' => $section['type'],
                'title' => $section['title'],
                'body' => in_array($section['type'], [SectionType::PricingTable->value, SectionType::PaymentSchedule->value], true) ? null : $section['body'],
                'order' => $order,
            ]);
        }

        $this->quotation->lineItems()->delete();
        foreach ($this->lineItems as $order => $item) {
            $this->quotation->lineItems()->create([
                'service_name' => $item['service_name'],
                'service_category' => $item['service_category'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'],
                'tax' => $item['tax'],
                'line_total' => $this->lineTotal($item),
                'order' => $order,
            ]);
        }

        $this->quotation->paymentPhases()->delete();
        foreach ($this->paymentPhases as $order => $phase) {
            $this->quotation->paymentPhases()->create([
                'description' => $phase['description'],
                'amount' => $phase['amount'],
                'due_condition' => $phase['due_condition'],
                'order' => $order,
            ]);
        }

        $this->quotation->forceFill([
            'currency' => $this->currency,
            'version' => $wasSent ? $this->quotation->version + 1 : $this->quotation->version,
        ])->save();

        session()->flash('status', 'Quotation saved.');
    }

    public function saveAsTemplate(): void
    {
        $this->validate(['templateName' => ['required', 'string', 'max:255']]);

        $this->save();

        $template = QuotationTemplate::create([
            'created_by' => auth()->id(),
            'name' => $this->templateName,
        ]);

        foreach ($this->quotation->sections()->get() as $order => $section) {
            $template->sections()->create([
                'type' => $section->type,
                'title' => $section->title,
                'body' => $section->body,
                'order' => $order,
            ]);
        }

        foreach ($this->quotation->lineItems()->get() as $order => $item) {
            $template->lineItems()->create([
                'service_name' => $item->service_name,
                'service_category' => $item->service_category,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
                'tax' => $item->tax,
                'order' => $order,
            ]);
        }

        foreach ($this->quotation->paymentPhases()->get() as $order => $phase) {
            $template->paymentPhases()->create([
                'description' => $phase->description,
                'amount' => $phase->amount,
                'due_condition' => $phase->due_condition,
                'order' => $order,
            ]);
        }

        $template->tags()->sync($this->quotation->client->tags->pluck('id'));

        $this->showTemplateModal = false;
        $this->templateName = '';
        session()->flash('status', "Saved as template \"{$template->name}\".");
    }

    public function with(): array
    {
        return [
            'library' => SectionType::library(),
        ];
    }
};
?>

<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">{{ $quotation->reference }} &middot; {{ $quotation->client->company_name }}</p>
        </div>
        <div class="flex gap-2">
            <select wire:model="currency" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <option value="NGN">NGN</option>
                <option value="USD">USD</option>
                <option value="GBP">GBP</option>
                <option value="EUR">EUR</option>
            </select>
            <button wire:click="$set('showTemplateModal', true)" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Save as Template
            </button>
            <button wire:click="save" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">
                Save
            </button>
            <a href="{{ route('admin.quotations.show', $quotation) }}" class="rounded-xl bg-navy px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Preview &amp; Send
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div x-sort="$wire.reorderSection($item, $position)" class="space-y-4">
                @foreach ($sections as $i => $section)
                    <div x-sort:item="{{ $section['uid'] }}" wire:key="section-{{ $section['uid'] }}" class="rounded-2xl bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <div x-sort:handle class="flex cursor-move items-center gap-2 text-slate-300 hover:text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01"/></svg>
                                <span class="text-xs font-medium tracking-wide uppercase">{{ \App\Enums\SectionType::from($section['type'])->label() }}</span>
                            </div>
                            <div class="flex gap-1">
                                @if (! in_array($section['type'], ['pricing_table', 'payment_schedule'], true))
                                    <button type="button" wire:click="duplicateSection('{{ $section['uid'] }}')" class="rounded-lg px-2 py-1 text-xs text-slate-400 hover:bg-slate-50">Duplicate</button>
                                @endif
                                <button type="button" wire:click="removeSection('{{ $section['uid'] }}')" class="rounded-lg px-2 py-1 text-xs text-red-500 hover:bg-red-50">Delete</button>
                            </div>
                        </div>

                        <input type="text" wire:model.blur="sections.{{ $i }}.title" class="mt-3 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium focus:border-slate-400 focus:outline-none" placeholder="Section title">

                        @if ($section['type'] === 'pricing_table')
                            <div class="mt-4 space-y-2" x-data>
                                <div class="grid grid-cols-12 gap-2 px-1 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">
                                    <div class="col-span-4">Service</div>
                                    <div class="col-span-2">Qty</div>
                                    <div class="col-span-2">Unit Price</div>
                                    <div class="col-span-1">Disc.</div>
                                    <div class="col-span-1">Tax</div>
                                    <div class="col-span-2">Total</div>
                                </div>
                                @foreach ($lineItems as $li => $item)
                                    <div class="grid grid-cols-12 items-center gap-2" wire:key="line-{{ $item['uid'] }}">
                                        <input type="text" wire:model.blur="lineItems.{{ $li }}.service_name" placeholder="Website Design" class="col-span-4 rounded-lg border border-slate-200 px-2 py-1.5 text-sm">
                                        <input type="number" step="0.01" wire:model.blur="lineItems.{{ $li }}.quantity" class="col-span-2 rounded-lg border border-slate-200 px-2 py-1.5 text-sm">
                                        <input type="number" step="0.01" wire:model.blur="lineItems.{{ $li }}.unit_price" class="col-span-2 rounded-lg border border-slate-200 px-2 py-1.5 text-sm">
                                        <input type="number" step="0.01" wire:model.blur="lineItems.{{ $li }}.discount" class="col-span-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm">
                                        <input type="number" step="0.01" wire:model.blur="lineItems.{{ $li }}.tax" class="col-span-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm">
                                        <div class="col-span-1 text-sm font-medium text-slate-700">{{ number_format($this->lineTotal($item), 2) }}</div>
                                        <button type="button" wire:click="removeLineItem('{{ $item['uid'] }}')" class="col-span-1 text-xs text-red-400 hover:text-red-600">&times;</button>
                                    </div>
                                @endforeach

                                <button type="button" wire:click="addLineItem" class="mt-2 text-xs font-medium text-brand hover:text-brand-emphasis">+ Add line item</button>

                                <div class="mt-3 flex justify-end border-t border-slate-100 pt-3 text-sm font-semibold text-slate-900">
                                    Grand Total: {{ $currency }} {{ number_format($this->grandTotal(), 2) }}
                                </div>
                            </div>
                        @elseif ($section['type'] === 'payment_schedule')
                            <div class="mt-4 space-y-2">
                                <div class="grid grid-cols-12 gap-2 px-1 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">
                                    <div class="col-span-5">Description</div>
                                    <div class="col-span-3">Amount</div>
                                    <div class="col-span-3">Due Condition</div>
                                </div>
                                @foreach ($paymentPhases as $pi => $phase)
                                    <div class="grid grid-cols-12 items-center gap-2" wire:key="phase-{{ $phase['uid'] }}">
                                        <input type="text" wire:model.blur="paymentPhases.{{ $pi }}.description" placeholder="Deposit" class="col-span-5 rounded-lg border border-slate-200 px-2 py-1.5 text-sm">
                                        <input type="number" step="0.01" wire:model.blur="paymentPhases.{{ $pi }}.amount" class="col-span-3 rounded-lg border border-slate-200 px-2 py-1.5 text-sm">
                                        <input type="text" wire:model.blur="paymentPhases.{{ $pi }}.due_condition" placeholder="Due on signing" class="col-span-3 rounded-lg border border-slate-200 px-2 py-1.5 text-sm">
                                        <button type="button" wire:click="removePaymentPhase('{{ $phase['uid'] }}')" class="col-span-1 text-xs text-red-400 hover:text-red-600">&times;</button>
                                    </div>
                                @endforeach

                                <button type="button" wire:click="addPaymentPhase" class="mt-2 text-xs font-medium text-brand hover:text-brand-emphasis">+ Add payment phase</button>

                                <div class="mt-3 flex justify-end border-t border-slate-100 pt-3 text-sm font-semibold text-slate-900">
                                    Total: {{ $currency }} {{ number_format($this->paymentPhasesTotal(), 2) }}
                                </div>
                            </div>
                        @else
                            <div
                                wire:ignore
                                x-data="{
                                    quill: null,
                                    init() {
                                        this.quill = new Quill(this.$refs.editor, {
                                            theme: 'snow',
                                            modules: { toolbar: [['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['link','image'],['clean']] },
                                        });
                                        this.quill.root.innerHTML = @js($section['body'] ?? '');
                                        this.quill.on('text-change', () => {
                                            $wire.set('sections.{{ $i }}.body', this.quill.root.innerHTML, false);
                                        });
                                    }
                                }"
                                class="mt-3"
                            >
                                <div x-ref="editor" style="min-height: 140px;"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if (empty($sections))
                <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-400">
                    No sections yet. Add one from the library on the right.
                </div>
            @endif
        </div>

        <div>
            <div class="sticky top-6 rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Add a Section</h2>
                <div class="mt-3 space-y-1">
                    @foreach ($library as $type)
                        <button type="button" wire:click="addSection('{{ $type->value }}')" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-600 hover:bg-slate-50">
                            {{ $type->label() }}
                        </button>
                    @endforeach
                    <button type="button" wire:click="addSection('pricing_table')" class="block w-full rounded-lg bg-slate-50 px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100">
                        + Pricing Table
                    </button>
                    <button type="button" wire:click="addSection('custom')" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-600 hover:bg-slate-50">
                        + Custom Section
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($showTemplateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-sm font-semibold text-slate-900">Save as Template</h2>
                <input type="text" wire:model="templateName" placeholder="e.g. Healthcare Website Package" class="mt-3 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                @error('templateName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="$set('showTemplateModal', false)" class="rounded-xl px-4 py-2 text-sm text-slate-500">Cancel</button>
                    <button wire:click="saveAsTemplate" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">Save Template</button>
                </div>
            </div>
        </div>
    @endif
</div>
