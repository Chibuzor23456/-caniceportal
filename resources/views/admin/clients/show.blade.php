<x-layouts.admin :page-title="$client->company_name" title="{{ $client->company_name }}">
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">{{ $client->company_name }}</h2>
                    <x-ui.pill :color="$client->status->pillColor()">{{ $client->status->label() }}</x-ui.pill>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $client->contact_person }} &middot; {{ $client->email }}</p>
                <div class="mt-3 flex flex-wrap gap-1">
                    @foreach ($client->tags as $tag)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ $tag->name }}</span>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('admin.clients.edit', $client) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Edit
            </a>
        </div>

        <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-100 pt-6 text-sm sm:grid-cols-4">
            <div>
                <dt class="text-xs tracking-wide text-slate-400 uppercase">Phone</dt>
                <dd class="mt-1 text-slate-700">{{ $client->phone ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs tracking-wide text-slate-400 uppercase">Industry</dt>
                <dd class="mt-1 text-slate-700">{{ $client->industry ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs tracking-wide text-slate-400 uppercase">Date Joined</dt>
                <dd class="mt-1 text-slate-700">{{ $client->date_joined->format('M j, Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs tracking-wide text-slate-400 uppercase">Referral Source</dt>
                <dd class="mt-1 text-slate-700">{{ $client->referral_source ?: '-' }}</dd>
            </div>
        </dl>

        @if ($client->address || $client->notes)
            <div class="mt-6 grid grid-cols-1 gap-4 border-t border-slate-100 pt-6 text-sm sm:grid-cols-2">
                @if ($client->address)
                    <div>
                        <dt class="text-xs tracking-wide text-slate-400 uppercase">Address</dt>
                        <dd class="mt-1 text-slate-700">{{ $client->address }}</dd>
                    </div>
                @endif
                @if ($client->notes)
                    <div>
                        <dt class="text-xs tracking-wide text-slate-400 uppercase">Notes</dt>
                        <dd class="mt-1 whitespace-pre-line text-slate-700">{{ $client->notes }}</dd>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (['Quotations', 'Projects', 'Invoices', 'Files', 'Messages'] as $section)
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">{{ $section }}</h3>
                <p class="mt-6 text-center text-sm text-slate-400">Coming in a later phase.</p>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
