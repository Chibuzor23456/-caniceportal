<x-layouts.admin page-title="Activity Log" title="Activity Log">
    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.activity.index', ['tab' => 'activity']) }}" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $tab === 'activity' ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">Activity</a>
        <a href="{{ route('admin.activity.index', ['tab' => 'email']) }}" class="rounded-full px-3 py-1.5 text-sm font-medium {{ $tab === 'email' ? 'bg-brand text-white' : 'bg-white text-slate-500 shadow-sm' }}">Email Delivery</a>
    </div>

    @if ($tab === 'activity')
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <ul class="divide-y divide-slate-50">
                @forelse ($activity as $entry)
                    <li class="flex items-start gap-3 px-5 py-4">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-700">{{ $entry->description }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ $entry->causer?->name ?? 'System' }} &middot; {{ $entry->created_at->diffForHumans() }}
                                @if ($entry->client)
                                    &middot; {{ $entry->client->company_name }}
                                @endif
                            </p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-slate-400">Nothing's happened yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="mt-4">{{ $activity->links() }}</div>
    @else
        <div class="overflow-x-auto rounded-2xl bg-white shadow-sm">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                    <tr>
                        <th class="px-5 py-3">Recipient</th>
                        <th class="px-5 py-3">Subject</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Sent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($emailLogs as $log)
                        <tr>
                            <td class="px-5 py-3 text-slate-700">{{ $log->recipient }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $log->subject }}</td>
                            <td class="px-5 py-3"><x-ui.pill :color="$log->status->pillColor()">{{ $log->status->label() }}</x-ui.pill></td>
                            <td class="px-5 py-3 text-slate-400">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">No emails sent yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $emailLogs->links() }}</div>
    @endif
</x-layouts.admin>
