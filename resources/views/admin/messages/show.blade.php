<x-layouts.admin :page-title="$client->company_name" :title="$client->company_name">
    <div class="mb-4">
        <a href="{{ route('admin.messages.index') }}" class="text-sm font-medium text-slate-400 hover:text-slate-600">&larr; All Messages</a>
    </div>

    <livewire:messages.thread :client="$client" role="admin" />
</x-layouts.admin>
