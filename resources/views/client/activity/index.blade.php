<x-layouts.client page-title="Activity" title="Activity">
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
        <ul class="divide-y divide-slate-50">
            @forelse ($activity as $entry)
                <li class="flex items-start gap-3 px-5 py-4">
                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                    <div>
                        <p class="text-sm text-slate-700">{{ $entry->description }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $entry->created_at->diffForHumans() }}</p>
                    </div>
                </li>
            @empty
                <li class="px-5 py-12 text-center text-sm text-slate-400">Nothing to show yet. This fills in as your project progresses.</li>
            @endforelse
        </ul>
    </div>

    <div class="mt-4">{{ $activity->links() }}</div>
</x-layouts.client>
