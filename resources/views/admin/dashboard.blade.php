<x-layouts.admin page-title="Overview" :freshness="'Updated '.now()->diffForHumans(null, true).' ago'" title="Overview">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <x-ui.stat-card label="Total Clients" :value="$totalClients" color="blue" :delta="$clientsDelta">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Active Projects" :value="$activeProjects" color="green">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6v6H9z"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Pending Quotations" :value="$pendingQuotations" color="orange">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Expiring Quotations" :value="$expiringQuotations" color="orange">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Pending Invoices" :value="$pendingInvoices" color="purple">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Phases Awaiting Review" :value="$phasesAwaitingReview" color="slate">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Total Revenue" :value="$currency.' '.number_format($totalRevenue, 0)" color="green">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Paid Invoices" :value="$paidInvoices" color="green">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M20 6 9 17l-5-5"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card label="Overdue Invoices" :value="$overdueInvoices" color="orange">
            <x-slot:icon>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Quotation Activity</h2>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $quotationActivity['currentTotal'] }}</p>
                    <p class="flex items-center gap-1 text-xs font-medium {{ $quotationActivity['delta'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        <span>{{ $quotationActivity['delta'] >= 0 ? '↑' : '↓' }}</span>
                        <span>{{ abs($quotationActivity['delta']) }}% vs last week</span>
                    </p>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-400">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-brand"></span> Sent</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> Accepted</span>
                </div>
            </div>

            <div class="mt-6 flex h-40 items-end gap-3">
                @foreach ($quotationActivity['weeks'] as $week)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <div class="flex h-32 w-full items-end gap-1">
                            <div class="flex-1 rounded-t bg-brand" style="height: {{ max(2, ($week['sent'] / $quotationActivity['max']) * 100) }}%"></div>
                            <div class="flex-1 rounded-t bg-emerald-400" style="height: {{ max(2, ($week['accepted'] / $quotationActivity['max']) * 100) }}%"></div>
                        </div>
                        <span class="text-[10px] text-slate-400">{{ $week['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Recent Activity</h2>
            <ul class="mt-4 space-y-4">
                @forelse ($recentActivity as $entry)
                    <li class="flex gap-3">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                        <div>
                            <p class="text-sm text-slate-700">{{ $entry->description }}</p>
                            <p class="text-xs text-slate-400">{{ $entry->created_at->diffForHumans() }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">Nothing's happened yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Recent Client Messages</h2>
            <p class="mt-8 text-center text-sm text-slate-400">Coming in a later phase.</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Calendar Overview</h2>
            <p class="mt-8 text-center text-sm text-slate-400">Coming in a later phase.</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Email Delivery Health</h2>
            <p class="mt-8 text-center text-sm text-slate-400">Coming in a later phase.</p>
        </div>
    </div>
</x-layouts.admin>
