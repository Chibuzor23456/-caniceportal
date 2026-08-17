<x-layouts.admin :page-title="$contract->reference" :title="$contract->reference">
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">{{ $contract->title }}</h2>
                    <x-ui.pill :color="$contract->status->pillColor()">{{ $contract->status->label() }}</x-ui.pill>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $contract->client->company_name }} &middot; {{ $contract->reference }}
                    @if ($contract->project)
                        &middot; <a href="{{ route('admin.projects.show', $contract->project) }}" class="text-brand hover:text-brand-emphasis">{{ $contract->project->title }}</a>
                    @endif
                </p>
            </div>

            <div class="flex gap-2">
                @if ($contract->status->value === 'draft')
                    <form method="POST" action="{{ route('admin.contracts.send', $contract) }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">Send to Client</button>
                    </form>
                @endif

                @if ($pdfUrl)
                    <a href="{{ $pdfUrl }}" target="_blank" class="rounded-xl bg-navy px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                        {{ $contract->isUploaded() ? 'View File' : 'View PDF' }}
                    </a>
                @endif
            </div>
        </div>

        @if ($contract->status->value === 'rejected' && $contract->rejection_reason)
            <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">
                <strong>Client feedback:</strong> {{ $contract->rejection_reason }}
            </div>
        @endif
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
        @if ($contract->isUploaded())
            <p class="text-sm text-slate-500">This contract was created by uploading a document.</p>
        @else
            <div class="prose prose-sm max-w-none text-slate-700">{!! $contract->body !!}</div>
        @endif
    </div>
</x-layouts.admin>
