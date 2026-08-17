<x-layouts.admin :page-title="$quotation->reference" :title="$quotation->reference">
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">{{ $quotation->client->company_name }}</h2>
                    <x-ui.pill :color="$quotation->status->pillColor()">{{ $quotation->status->label() }}</x-ui.pill>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $quotation->reference }} &middot; v{{ $quotation->version }} &middot;
                    Issued {{ $quotation->issue_date?->format('M j, Y') }}
                    @if ($quotation->expiry_date)
                        &middot; Expires {{ $quotation->expiry_date->format('M j, Y') }}
                    @endif
                </p>
            </div>

            <div class="flex gap-2">
                @if ($quotation->status->value === 'draft')
                    <a href="{{ route('admin.quotations.edit', $quotation) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Edit</a>
                    <form method="POST" action="{{ route('admin.quotations.send', $quotation) }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">Send to Client</button>
                    </form>
                @elseif (in_array($quotation->status->value, ['sent', 'viewed']))
                    <a href="{{ route('admin.quotations.edit', $quotation) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Revise</a>
                @endif

                @if ($pdfUrl)
                    <a href="{{ $pdfUrl }}" target="_blank" class="rounded-xl bg-navy px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">View PDF</a>
                @endif
            </div>
        </div>

        @if ($quotation->status->value === 'rejected' && $quotation->rejection_reason)
            <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">
                <strong>Client feedback:</strong> {{ $quotation->rejection_reason }}
            </div>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                @forelse ($quotation->sections as $section)
                    <h3 class="mt-6 text-sm font-semibold tracking-wide text-slate-900 uppercase first:mt-0">{{ $section->title }}</h3>

                    @if ($section->type === \App\Enums\SectionType::PricingTable)
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full min-w-[520px] text-left text-sm">
                                <thead class="border-b border-slate-100 text-xs text-slate-400 uppercase">
                                    <tr><th class="py-2">Service</th><th class="py-2">Qty</th><th class="py-2">Unit Price</th><th class="py-2">Total</th></tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @foreach ($quotation->lineItems as $item)
                                        <tr>
                                            <td class="py-2">{{ $item->service_name }}</td>
                                            <td class="py-2">{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                                            <td class="py-2">{{ $quotation->currency }} {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="py-2">{{ $quotation->currency }} {{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="font-semibold text-slate-900">
                                        <td colspan="3" class="py-2 text-right">Grand Total</td>
                                        <td class="py-2">{{ $quotation->currency }} {{ number_format($quotation->grandTotal(), 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @elseif ($section->type === \App\Enums\SectionType::PaymentSchedule)
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full min-w-[520px] text-left text-sm">
                                <thead class="border-b border-slate-100 text-xs text-slate-400 uppercase">
                                    <tr><th class="py-2">Description</th><th class="py-2">Amount</th><th class="py-2">Due Condition</th></tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @foreach ($quotation->paymentPhases as $phase)
                                        <tr>
                                            <td class="py-2">{{ $phase->description }}</td>
                                            <td class="py-2">{{ $quotation->currency }} {{ number_format($phase->amount, 2) }}</td>
                                            <td class="py-2">{{ $phase->due_condition }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="font-semibold text-slate-900">
                                        <td colspan="2" class="py-2 text-right">Total</td>
                                        <td class="py-2">{{ $quotation->currency }} {{ number_format($quotation->paymentPhases->sum('amount'), 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="prose prose-sm mt-3 max-w-none text-slate-700">{!! $section->body !!}</div>
                    @endif
                @empty
                    <p class="text-sm text-slate-400">No sections yet.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Version History</h3>
                <div class="mt-3 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">v{{ $quotation->version }} (current)</span>
                        <span class="text-xs text-slate-400">{{ $quotation->updated_at->diffForHumans() }}</span>
                    </div>
                    @forelse ($quotation->versions as $version)
                        <div class="flex items-center justify-between text-sm">
                            <a href="{{ route('admin.quotations.versions.show', [$quotation, $version]) }}" class="text-slate-500 hover:text-brand">v{{ $version->version_number }}</a>
                            <span class="text-xs text-slate-400">{{ $version->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No earlier versions.</p>
                    @endforelse
                </div>
            </div>

            @if ($quotation->project)
                <div class="rounded-2xl bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Project</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ $quotation->project->title }} was created automatically when this quotation was accepted.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
