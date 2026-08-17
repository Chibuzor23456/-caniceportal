<x-layouts.admin :page-title="$invoice->reference" :title="$invoice->reference">
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">{{ $invoice->reference }}</h2>
                    <x-ui.pill :color="$invoice->status->pillColor()">{{ $invoice->status->label() }}</x-ui.pill>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $invoice->client->company_name }}
                    &middot; <a href="{{ route('admin.projects.show', $invoice->project) }}" class="text-brand hover:text-brand-emphasis">{{ $invoice->project->title }}</a>
                    @if ($invoice->due_date)
                        &middot; Due {{ $invoice->due_date->format('M j, Y') }}
                    @endif
                </p>
            </div>

            <div class="flex gap-2">
                @if ($pdfUrl)
                    <a href="{{ $pdfUrl }}" target="_blank" class="rounded-xl bg-navy px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">View PDF</a>
                @endif

                @if ($invoice->status->value === 'draft')
                    <form method="POST" action="{{ route('admin.invoices.send', $invoice) }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">Send to Client</button>
                    </form>
                @elseif (in_array($invoice->status->value, ['sent', 'overdue']))
                    <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Mark Paid</button>
                    </form>
                @endif

                @if (! in_array($invoice->status->value, ['paid', 'cancelled']))
                    <form method="POST" action="{{ route('admin.invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this invoice?');">
                        @csrf
                        <button type="submit" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Cancel</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($invoice->status->value === 'draft')
                <div class="rounded-2xl bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Edit Draft</h3>
                    <p class="mt-1 text-xs text-slate-400">
                        @if ($invoice->paymentPhase)
                            Pre-filled from the "{{ $invoice->paymentPhase->description }}" payment phase on the signed quotation. Edit before sending if needed.
                        @else
                            No matching payment phase was found on the quotation - fill this in manually.
                        @endif
                    </p>

                    <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Description</label>
                            <input type="text" name="description" value="{{ old('description', $invoice->description) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Amount ({{ $invoice->currency }})</label>
                                <input type="number" step="0.01" name="amount" value="{{ old('amount', $invoice->amount) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Due Date</label>
                                <input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                @error('due_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Internal Notes</label>
                            <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('notes', $invoice->notes) }}</textarea>
                        </div>

                        <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">Save Draft</button>
                    </form>
                </div>
            @else
                <div class="rounded-2xl bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">{{ $invoice->description }}</h3>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</p>

                    @if ($invoice->notes)
                        <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                            <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Internal Notes</p>
                            <p class="mt-1">{{ $invoice->notes }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Payment Proof</h3>
                    @if ($invoice->payment_proof_path)
                        <p class="mt-2 text-sm text-slate-600">Uploaded {{ $invoice->payment_proof_uploaded_at?->diffForHumans() }}.</p>
                    @else
                        <p class="mt-8 text-center text-sm text-slate-400">The client hasn't uploaded proof of payment yet.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Timeline</h3>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><span class="text-slate-500">Issued</span><span class="text-slate-700">{{ $invoice->issue_date?->format('M j, Y') }}</span></div>
                    @if ($invoice->sent_at)
                        <div class="flex items-center justify-between"><span class="text-slate-500">Sent</span><span class="text-slate-700">{{ $invoice->sent_at->format('M j, Y') }}</span></div>
                    @endif
                    @if ($invoice->paid_at)
                        <div class="flex items-center justify-between"><span class="text-slate-500">Paid</span><span class="text-slate-700">{{ $invoice->paid_at->format('M j, Y') }}</span></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
