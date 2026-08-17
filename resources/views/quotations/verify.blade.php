<x-layouts.bare title="Verify Document">
    <div class="mx-auto max-w-lg px-4 py-16">
        <div class="mb-6 flex justify-center">
            <img src="{{ asset('images/brand/logo-full.png') }}" alt="Canice Technologies" class="h-8 w-auto">
        </div>

        <div class="rounded-2xl bg-white p-8 text-center shadow-sm">
            @if ($quotation->status === \App\Enums\QuotationStatus::Accepted && $quotation->signature)
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
                </div>
                <h1 class="mt-4 text-lg font-bold text-slate-900">Document Verified</h1>
                <p class="mt-1 text-sm text-slate-500">This is a genuine, digitally-signed Canice Technologies document.</p>

                <dl class="mt-6 space-y-3 text-left text-sm">
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-400">Reference</dt>
                        <dd class="font-medium text-slate-900">{{ $quotation->reference }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-400">Signed by</dt>
                        <dd class="font-medium text-slate-900">{{ $quotation->signature->signer_name }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-400">Signed on</dt>
                        <dd class="font-medium text-slate-900">{{ $quotation->signature->signed_at->format('F j, Y \a\t g:i A') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">IP address</dt>
                        <dd class="font-medium text-slate-900">{{ $quotation->signature->ip_address }}</dd>
                    </div>
                </dl>
            @else
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                </div>
                <h1 class="mt-4 text-lg font-bold text-slate-900">Not Yet Signed</h1>
                <p class="mt-1 text-sm text-slate-500">Reference {{ $quotation->reference }} has not been signed, so there's nothing to verify yet.</p>
            @endif
        </div>
    </div>
</x-layouts.bare>
