<x-layouts.admin page-title="Messages" title="Messages">
    <div class="grid grid-cols-1 gap-3">
        @forelse ($clients as $client)
            @php $latest = $client->messages->first(); @endphp
            <a href="{{ route('admin.messages.show', $client) }}" class="flex items-center justify-between rounded-2xl bg-white p-5 shadow-sm hover:shadow-md">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-slate-900">{{ $client->company_name }}</p>
                        @if ($client->unread_count)
                            <span class="rounded-full bg-brand px-2 py-0.5 text-xs font-semibold text-white">{{ $client->unread_count }}</span>
                        @endif
                    </div>
                    <p class="mt-1 truncate text-sm text-slate-500">
                        {{ $latest?->body ?: ($latest ? 'Attachment' : 'No messages yet') }}
                    </p>
                </div>
                @if ($latest)
                    <span class="shrink-0 text-xs text-slate-400">{{ $latest->created_at->diffForHumans() }}</span>
                @endif
            </a>
        @empty
            <div class="rounded-2xl bg-white p-12 text-center shadow-sm">
                <p class="text-sm text-slate-400">No clients yet.</p>
            </div>
        @endforelse
    </div>
</x-layouts.admin>
