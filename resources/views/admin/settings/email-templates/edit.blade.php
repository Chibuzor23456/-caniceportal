<x-layouts.admin :page-title="$label" :title="$label">
    <x-admin.settings-tabs active="email-templates" />

    <a href="{{ route('admin.settings.email-templates.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-slate-700">&larr; All templates</a>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.settings.email-templates.update', $template) }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-slate-700">Subject</label>
                <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                @error('subject') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Body</label>
                <textarea name="body" rows="10" placeholder="Leave blank to use the default design for this email." class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono focus:border-slate-400 focus:outline-none">{{ old('body', $template->body) }}</textarea>
                <p class="mt-1 text-xs text-slate-400">Markdown is supported. Leave blank to keep sending this email's original design.</p>
                @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if (! empty($template->variables))
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase">Available variables</p>
                    <p class="mt-1 font-mono text-xs text-slate-600">
                        @foreach ($template->variables as $variable)
                            {{ '{{ '.$variable.' }}' }}@if (! $loop->last), @endif
                        @endforeach
                    </p>
                </div>
            @endif

            <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-emphasis">
                Save Template
            </button>
        </form>
    </div>
</x-layouts.admin>
