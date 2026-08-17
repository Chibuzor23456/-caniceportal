<x-layouts.client :page-title="$invoice->reference" :title="$invoice->reference">
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
                    {{ $invoice->description }}
                    @if ($invoice->due_date)
                        &middot; Due {{ $invoice->due_date->format('M j, Y') }}
                    @endif
                </p>
            </div>

            <div class="text-right">
                <p class="text-2xl font-bold text-slate-900">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</p>
                @if ($pdfUrl)
                    <a href="{{ $pdfUrl }}" target="_blank" class="mt-2 inline-block text-sm font-medium text-brand hover:text-brand-emphasis">Download PDF</a>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Payment Proof</h3>

            @if ($invoice->payment_proof_path)
                <p class="mt-2 text-sm text-slate-600">You uploaded proof of payment on {{ $invoice->payment_proof_uploaded_at?->format('M j, Y') }}. We'll confirm once it's verified.</p>
            @elseif (in_array($invoice->status->value, ['sent', 'overdue']))
                <p class="mt-2 text-sm text-slate-500">Once you've made the transfer, upload your proof of payment below and we'll verify it.</p>

                <form method="POST" action="{{ route('client.invoices.payment-proof', $invoice) }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-2 sm:flex-row">
                    @csrf
                    <input type="file" name="proof" required class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">Upload Proof</button>
                </form>
                @error('proof') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @else
                <p class="mt-8 text-center text-sm text-slate-400">Nothing to show here.</p>
            @endif
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Payment / Bank Details</h3>
            @php $bankDetails = \App\Models\CompanySetting::current()->bank_details; @endphp
            @if ($bankDetails)
                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $bankDetails }}</p>
            @else
                <p class="mt-2 text-sm text-slate-400">Bank details not set up yet - contact Canice Technologies directly.</p>
            @endif
        </div>
    </div>
</x-layouts.client>
