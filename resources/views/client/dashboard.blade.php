<x-layouts.client page-title="Overview" :freshness="'Updated '.now()->diffForHumans(null, true).' ago'" title="Overview">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ $client?->contact_person ?? auth()->user()->name }}.</h2>
        <p class="mt-1 text-sm text-slate-500">Here's where things stand on your account.</p>
    </div>

    @if ($pendingTestimonial)
        <div x-data="{ open: true }" class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">How did we do on &ldquo;{{ $pendingTestimonial->project->title }}&rdquo;?</h2>
                    <p class="mt-1 text-sm text-slate-500">Your project is complete. We'd love to hear your feedback.</p>
                </div>
                <button type="button" @click="open = false" x-show="open" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form x-show="open" method="POST" action="{{ route('client.testimonials.store', $pendingTestimonial) }}" class="mt-4">
                @csrf
                <div class="flex items-center gap-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer text-2xl text-slate-300 has-[:checked]:text-amber-400">
                            <input type="radio" name="rating" value="{{ $i }}" class="sr-only" required>&#9733;
                        </label>
                    @endfor
                </div>
                <textarea name="comment" rows="2" placeholder="Anything you'd like to add? (optional)" class="mt-3 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea>
                <button type="submit" class="mt-3 rounded-xl bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-emphasis">Submit Feedback</button>
            </form>
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-ui.stat-card label="Active Projects" :value="$activeProjects" color="blue">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6v6H9z"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Current Progress" :value="is_null($currentProgress) ? '0%' : $currentProgress.'%'" color="green" :zero-state="is_null($currentProject)">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Current Phase Status" :value="$currentPhaseStatus ?? '-'" color="orange" :zero-state="is_null($currentPhaseStatus)">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Outstanding Invoices" :value="$outstandingInvoices" color="purple">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Files Shared" :value="$filesCount" color="slate">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-sm font-semibold text-slate-900">Project Progress</h2>
            @if ($progressTrend)
                <div class="mt-6 flex h-40 items-end gap-3">
                    @foreach ($progressTrend['points'] as $point)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="flex h-32 w-full items-end">
                                <div class="w-full rounded-t bg-brand" style="height: {{ max(2, $point['percentage']) }}%"></div>
                            </div>
                            <span class="text-[10px] text-slate-400">{{ $point['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mt-8 flex h-40 flex-col items-center justify-center rounded-xl bg-slate-50 text-center">
                    <p class="text-sm text-slate-400">No project yet. This fills in once your first quotation is accepted.</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Notifications</h2>
            <p class="mt-8 text-center text-sm text-slate-400">Nothing yet.</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Pending Quotations</h2>
            @forelse ($pendingQuotations as $quotation)
                <a href="{{ route('client.quotations.show', $quotation) }}" class="mt-3 flex items-center justify-between border-t border-slate-50 py-2 text-sm first:border-t-0 first:pt-0">
                    <span class="text-slate-700">{{ $quotation->reference }}</span>
                    <span class="text-xs text-slate-400">Expires {{ $quotation->expiry_date?->format('M j') }}</span>
                </a>
            @empty
                <p class="mt-8 text-center text-sm text-slate-400">Nothing awaiting your decision.</p>
            @endforelse
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Upcoming Meetings</h2>
            <p class="mt-8 text-center text-sm text-slate-400">Coming in a later phase.</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Document Archive</h2>
            <p class="mt-4 text-sm text-slate-500">Every signed quotation, signed contract, and paid invoice, in one place.</p>
            <a href="{{ route('client.documents.index') }}" class="mt-4 inline-block text-sm font-medium text-brand hover:text-brand-emphasis">Browse Documents &rarr;</a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Project Phase Breakdown</h2>
            <div class="mt-6">
                @if (count($phaseBreakdown) > 0)
                    <x-ui.donut :segments="$phaseBreakdown" />
                @else
                    <p class="text-center text-sm text-slate-400">No project yet.</p>
                @endif
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Contract Status</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($contracts as $contract)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ $contract->title }}</span>
                        <x-ui.pill :color="$contract->status->pillColor()">{{ $contract->status->label() }}</x-ui.pill>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No contracts yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Shared Files by Category</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($filesByCategory as $category => $count)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ $category }}</span>
                        <span class="font-medium text-slate-900">{{ $count }}</span>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No files shared yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Payment History</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($paymentHistory as $invoice)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ $invoice->reference }}</span>
                        <x-ui.pill :color="$invoice->status->pillColor()">{{ $invoice->status->label() }}</x-ui.pill>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No invoices yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Quotation History</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($quotationHistory as $quotation)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ $quotation->reference }}</span>
                        <x-ui.pill :color="$quotation->status->pillColor()">{{ $quotation->status->label() }}</x-ui.pill>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No quotations yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Smart Insights</h2>
            @if ($averageResponseMinutes !== null)
                <p class="mt-2 text-xs text-slate-400">Average response time: {{ $averageResponseMinutes < 60 ? $averageResponseMinutes.' min' : round($averageResponseMinutes / 60, 1).' hr' }}</p>
            @endif
            <ul class="mt-4 space-y-3">
                @forelse ($insights as $insight)
                    <li class="flex gap-2 text-sm text-slate-700">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                        {{ $insight }}
                    </li>
                @empty
                    <li class="text-sm text-slate-400">You're all caught up.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.client>
