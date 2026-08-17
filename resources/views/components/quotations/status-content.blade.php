@props(['quotation', 'pdfUrl' => null])

@php
    $expired = $quotation->status === \App\Enums\QuotationStatus::Expired || $quotation->isExpired();
@endphp

<div class="mx-auto max-w-3xl px-4 py-10">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $quotation->reference }}</h1>
            <p class="text-sm text-slate-500">{{ $quotation->client->company_name }}</p>
        </div>
        <x-ui.pill :color="$quotation->status->pillColor()">{{ $quotation->status->label() }}</x-ui.pill>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    @if ($quotation->status === \App\Enums\QuotationStatus::Accepted)
        {{-- Read-only signed view (Section 10). No signature-capture markup below this point. --}}
        <div class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800">
            Signed by <strong>{{ $quotation->signature?->signer_name }}</strong>
            on {{ $quotation->signature?->signed_at?->format('F j, Y \a\t g:i A') }}.
        </div>

        <div class="mt-6 rounded-2xl bg-white p-4 shadow-sm">
            @if ($pdfUrl)
                <iframe src="{{ $pdfUrl }}" class="h-[70vh] w-full rounded-xl border border-slate-100"></iframe>
                <a href="{{ $pdfUrl }}" download class="mt-4 inline-block rounded-xl bg-brand px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Download PDF
                </a>
            @else
                <p class="text-sm text-slate-400">The signed PDF is being prepared, check back shortly.</p>
            @endif
        </div>
    @elseif ($quotation->status === \App\Enums\QuotationStatus::Rejected)
        <div class="rounded-2xl bg-red-50 p-4 text-sm text-red-800">
            This quotation was declined on {{ $quotation->rejected_at?->format('F j, Y') }}.
        </div>
    @elseif ($expired)
        <div class="rounded-2xl bg-orange-50 p-4 text-sm text-orange-800">
            This quotation expired on {{ $quotation->expiry_date?->format('F j, Y') }}.
        </div>

        <form method="POST" action="{{ route('quotation.secure.request-revision', $quotation->secure_token) }}" class="mt-4">
            @csrf
            <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                Request a Revised Quotation
            </button>
        </form>
    @else
        {{-- Draft/Sent/Viewed, not expired: normal acceptance form + signature pad --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            @foreach ($quotation->sections as $section)
                <h2 class="mt-6 text-sm font-semibold tracking-wide text-slate-900 uppercase first:mt-0">{{ $section->title }}</h2>

                @if ($section->type === \App\Enums\SectionType::PricingTable)
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead class="border-b border-slate-100 text-xs text-slate-400 uppercase">
                                <tr>
                                    <th class="py-2">Service</th>
                                    <th class="py-2">Qty</th>
                                    <th class="py-2">Unit Price</th>
                                    <th class="py-2">Total</th>
                                </tr>
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
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead class="border-b border-slate-100 text-xs text-slate-400 uppercase">
                                <tr>
                                    <th class="py-2">Description</th>
                                    <th class="py-2">Amount</th>
                                    <th class="py-2">Due Condition</th>
                                </tr>
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
            @endforeach
        </div>

        <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm" x-data="{ tab: 'typed', declining: false }">
            <h2 class="text-sm font-semibold text-slate-900">Accept this quotation</h2>

            <div class="mt-4 flex gap-2">
                <button type="button" @click="tab = 'typed'" :class="tab === 'typed' ? 'bg-brand text-white' : 'bg-slate-100 text-slate-500'" class="rounded-full px-3 py-1.5 text-sm font-medium">Type your name</button>
                <button type="button" @click="tab = 'drawn'" :class="tab === 'drawn' ? 'bg-brand text-white' : 'bg-slate-100 text-slate-500'" class="rounded-full px-3 py-1.5 text-sm font-medium">Draw signature</button>
            </div>

            <form method="POST" action="{{ route('quotation.secure.accept', $quotation->secure_token) }}" class="mt-4" x-data="signatureForm()">
                @csrf
                <input type="hidden" name="signature_type" :value="tab">
                <input type="hidden" name="signature_data" x-ref="signatureData">

                <div x-show="tab === 'typed'">
                    <label class="block text-sm font-medium text-slate-700">Full name</label>
                    <input type="text" name="signer_name" x-ref="typedName" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" placeholder="Jordan Blake">
                </div>

                <div x-show="tab === 'drawn'" x-cloak>
                    <label class="block text-sm font-medium text-slate-700">Signer's full name</label>
                    <input type="text" x-show="tab === 'drawn'" name="signer_name_drawn" x-ref="drawnName" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" placeholder="Jordan Blake">

                    <label class="mt-4 block text-sm font-medium text-slate-700">Draw your signature</label>
                    <canvas x-ref="canvas" width="600" height="180" class="mt-1 w-full touch-none rounded-xl border border-slate-200 bg-slate-50"></canvas>
                    <button type="button" @click="clearPad()" class="mt-2 text-xs font-medium text-slate-400 hover:text-slate-600">Clear</button>
                </div>

                <button type="submit" @click="prepareSubmit" class="mt-6 w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Confirm & Accept
                </button>
            </form>

            <button type="button" @click="declining = !declining" class="mt-4 text-xs font-medium text-slate-400 hover:text-slate-600">
                Decline this quotation instead
            </button>

            <form x-show="declining" x-cloak method="POST" action="{{ route('quotation.secure.reject', $quotation->secure_token) }}" class="mt-3">
                @csrf
                <textarea name="reason" required rows="3" placeholder="Let us know what's not working for you" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
                <button type="submit" class="mt-2 rounded-xl border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                    Decline Quotation
                </button>
            </form>
        </div>
    @endif
</div>

<script>
    function signatureForm() {
        return {
            pad: null,
            init() {
                this.$nextTick(() => {
                    if (this.$refs.canvas) {
                        this.pad = new SignaturePad(this.$refs.canvas);
                    }
                });
            },
            clearPad() {
                this.pad?.clear();
            },
            prepareSubmit(event) {
                const drawn = this.$root.querySelector('input[name="signature_type"]').value === 'drawn';

                if (drawn) {
                    if (!this.pad || this.pad.isEmpty()) {
                        event.preventDefault();
                        alert('Please draw your signature first.');
                        return;
                    }
                    this.$refs.signatureData.value = this.pad.toDataURL('image/png');
                    this.$refs.typedName.value = this.$refs.drawnName.value;
                }
            },
        };
    }
</script>
