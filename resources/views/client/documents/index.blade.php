<x-layouts.client page-title="Documents" title="Documents">
    <div class="rounded-2xl bg-white shadow-sm">
        <div class="divide-y divide-slate-50">
            @forelse ($documents as $document)
                <a href="{{ $document['url'] }}" class="flex items-center justify-between px-5 py-4 hover:bg-slate-50">
                    <div class="flex items-center gap-3">
                        <x-ui.pill color="blue">{{ $document['type'] }}</x-ui.pill>
                        <span class="text-sm font-medium text-slate-900">{{ $document['title'] }}</span>
                    </div>
                    <span class="text-xs text-slate-400">{{ $document['date']?->format('M j, Y') }}</span>
                </a>
            @empty
                <p class="px-5 py-12 text-center text-sm text-slate-400">
                    Signed quotations, signed contracts, and paid invoices will show up here automatically.
                </p>
            @endforelse
        </div>
    </div>
</x-layouts.client>
