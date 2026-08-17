<x-layouts.client page-title="Contracts" title="Contracts">
    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[520px] text-left text-sm">
            <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-3">Reference</th>
                    <th class="px-5 py-3">Title</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($contracts as $contract)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $contract->reference }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $contract->title }}</td>
                        <td class="px-5 py-3"><x-ui.pill :color="$contract->status->pillColor()">{{ $contract->status->label() }}</x-ui.pill></td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('client.contracts.show', $contract) }}" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">No contracts yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.client>
