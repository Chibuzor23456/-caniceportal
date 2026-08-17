@props(['contract', 'pdfUrl' => null])

@php
    $expired = $contract->status === \App\Enums\ContractStatus::Expired;
@endphp

<div class="mx-auto max-w-3xl px-4 py-10">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ $contract->reference }}</h1>
            <p class="text-sm text-slate-500">{{ $contract->client->company_name }}</p>
        </div>
        <x-ui.pill :color="$contract->status->pillColor()">{{ $contract->status->label() }}</x-ui.pill>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    @if ($contract->status === \App\Enums\ContractStatus::Accepted)
        {{-- Read-only signed view (Section 10's post-signature lock, reused). No signature-capture markup below this point. --}}
        <div class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800">
            Signed by <strong>{{ $contract->signature?->signer_name }}</strong>
            on {{ $contract->signature?->signed_at?->format('F j, Y \a\t g:i A') }}.
        </div>

        <div class="mt-6 rounded-2xl bg-white p-4 shadow-sm">
            @if ($pdfUrl)
                <iframe src="{{ $pdfUrl }}" class="h-[70vh] w-full rounded-xl border border-slate-100"></iframe>
                <a href="{{ $pdfUrl }}" download class="mt-4 inline-block rounded-xl bg-brand px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                    Download
                </a>
            @else
                <p class="text-sm text-slate-400">The signed document is being prepared, check back shortly.</p>
            @endif
        </div>
    @elseif ($contract->status === \App\Enums\ContractStatus::Rejected)
        <div class="rounded-2xl bg-red-50 p-4 text-sm text-red-800">
            This contract was declined on {{ $contract->rejected_at?->format('F j, Y') }}.
        </div>
    @elseif ($expired)
        <div class="rounded-2xl bg-orange-50 p-4 text-sm text-orange-800">
            This contract expired on {{ $contract->expiry_date?->format('F j, Y') }}.
        </div>
    @else
        {{-- Draft/Sent/Viewed, not expired: content + acceptance form + signature pad --}}
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold tracking-wide text-slate-900 uppercase">{{ $contract->title }}</h2>

            @if ($contract->isUploaded())
                <p class="mt-3 text-sm text-slate-600">This contract was uploaded as a document.</p>
                @if ($pdfUrl)
                    <iframe src="{{ $pdfUrl }}" class="mt-3 h-[60vh] w-full rounded-xl border border-slate-100"></iframe>
                @endif
            @else
                <div class="prose prose-sm mt-3 max-w-none text-slate-700">{!! $contract->body !!}</div>
            @endif
        </div>

        <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm" x-data="{ tab: 'typed', declining: false }">
            <h2 class="text-sm font-semibold text-slate-900">Accept this contract</h2>

            <div class="mt-4 flex gap-2">
                <button type="button" @click="tab = 'typed'" :class="tab === 'typed' ? 'bg-brand text-white' : 'bg-slate-100 text-slate-500'" class="rounded-full px-3 py-1.5 text-sm font-medium">Type your name</button>
                <button type="button" @click="tab = 'drawn'" :class="tab === 'drawn' ? 'bg-brand text-white' : 'bg-slate-100 text-slate-500'" class="rounded-full px-3 py-1.5 text-sm font-medium">Draw signature</button>
            </div>

            <form method="POST" action="{{ route('contract.secure.accept', $contract->secure_token) }}" class="mt-4" x-data="signatureForm()">
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
                Decline this contract instead
            </button>

            <form x-show="declining" x-cloak method="POST" action="{{ route('contract.secure.reject', $contract->secure_token) }}" class="mt-3">
                @csrf
                <textarea name="reason" required rows="3" placeholder="Let us know what's not working for you" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"></textarea>
                <button type="submit" class="mt-2 rounded-xl border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                    Decline Contract
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
