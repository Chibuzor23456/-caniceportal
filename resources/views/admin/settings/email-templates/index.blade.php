<x-layouts.admin page-title="Email Templates" title="Email Templates">
    <x-admin.settings-tabs active="email-templates" />

    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
                <tr>
                    <th class="px-5 py-3">Template</th>
                    <th class="px-5 py-3">Subject</th>
                    <th class="px-5 py-3">Body</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach ($templates as $template)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $labels[$template->type] ?? $template->type }}</td>
                        <td class="px-5 py-3 max-w-xs truncate text-slate-500">{{ $template->subject }}</td>
                        <td class="px-5 py-3">
                            @if ($template->body)
                                <x-ui.pill color="green">Customized</x-ui.pill>
                            @else
                                <x-ui.pill color="gray">Default</x-ui.pill>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.settings.email-templates.edit', $template) }}" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
