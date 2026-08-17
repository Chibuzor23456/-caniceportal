<x-layouts.admin :page-title="$project->title" :title="$project->title">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">{{ $project->title }}</h2>
                    <x-ui.pill :color="$project->status->pillColor()">{{ $project->status->label() }}</x-ui.pill>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $project->client->company_name }}
                    &middot; From <a href="{{ route('admin.quotations.show', $project->quotation) }}" class="text-brand hover:text-brand-emphasis">{{ $project->quotation->reference }}</a>
                    @if ($project->expected_delivery_date)
                        &middot; Expected {{ $project->expected_delivery_date->format('M j, Y') }}
                    @endif
                    @if ($project->completion_date)
                        &middot; Completed {{ $project->completion_date->format('M j, Y') }}
                    @endif
                </p>
            </div>

            <div class="text-right">
                <p class="text-2xl font-bold text-slate-900">{{ $project->progressPercentage() }}%</p>
                <p class="text-xs text-slate-400">Progress</p>
            </div>
        </div>

        @if ($project->description)
            <p class="mt-4 text-sm text-slate-700">{{ $project->description }}</p>
        @endif

        @if ($project->notes)
            <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Internal Notes</p>
                <p class="mt-1">{{ $project->notes }}</p>
            </div>
        @endif
    </div>

    <div class="mt-6">
        <x-projects.timeline :project="$project" />
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Invoices</h3>
            <form method="POST" action="{{ route('admin.projects.invoices.store', $project) }}">
                @csrf
                <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">+ Create Invoice</button>
            </form>
        </div>

        <div class="mt-4 space-y-2">
            @forelse ($project->invoices as $invoice)
                <a href="{{ route('admin.invoices.show', $invoice) }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 hover:bg-slate-50">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-slate-900">{{ $invoice->reference }}</span>
                        <x-ui.pill :color="$invoice->status->pillColor()">{{ $invoice->status->label() }}</x-ui.pill>
                    </div>
                    <span class="text-sm text-slate-500">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</span>
                </a>
            @empty
                <p class="text-sm text-slate-400">No invoices created yet.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-6">
        <livewire:admin.projects.phase-manager :project="$project" />
    </div>

    <div class="mt-6 space-y-6">
        @foreach ($project->phases as $phase)
            <livewire:projects.phase-thread :phase="$phase" role="admin" :key="'phase-thread-'.$phase->id" />
        @endforeach
    </div>
</x-layouts.admin>
