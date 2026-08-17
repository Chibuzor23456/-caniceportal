<x-layouts.client page-title="Invoices" title="Invoices">
    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-3">Reference</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Amount</th>
                    <th class="px-5 py-3">Due</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $invoice->reference }}</td>
                        <td class="px-5 py-3"><x-ui.pill :color="$invoice->status->pillColor()">{{ $invoice->status->label() }}</x-ui.pill></td>
                        <td class="px-5 py-3 text-slate-500">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $invoice->due_date?->format('M j, Y') ?? '-' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('client.invoices.show', $invoice) }}" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No invoices yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.client>
