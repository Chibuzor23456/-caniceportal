<x-layouts.admin page-title="Notification Preferences" title="Notification Preferences">
    <x-admin.settings-tabs active="notifications" />

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">Choose which events show up in your notification bell. Emails for these events are sent separately and always go out regardless of this setting.</p>

        <form method="POST" action="{{ route('admin.settings.notifications.update') }}" class="mt-6 space-y-3">
            @csrf

            @foreach ($categories as $key => $label)
                <label class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3">
                    <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                    <input type="checkbox" name="enabled[]" value="{{ $key }}" @checked($preferences[$key] ?? true) class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand">
                </label>
            @endforeach

            <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                Save Preferences
            </button>
        </form>
    </div>
</x-layouts.admin>
