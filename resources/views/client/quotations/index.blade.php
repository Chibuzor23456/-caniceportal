<x-layouts.client page-title="Quotations" title="Quotations">
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-3">Reference</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Sent</th>
                    <th class="px-5 py-3">Expires</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($quotations as $quotation)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $quotation->reference }}</td>
                        <td class="px-5 py-3"><x-ui.pill :color="$quotation->status->pillColor()">{{ $quotation->status->label() }}</x-ui.pill></td>
                        <td class="px-5 py-3 text-slate-500">{{ $quotation->sent_at?->format('M j, Y') }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $quotation->expiry_date?->format('M j, Y') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('client.quotations.show', $quotation) }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No quotations yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.client>
