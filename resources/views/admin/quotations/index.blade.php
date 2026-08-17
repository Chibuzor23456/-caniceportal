<x-layouts.admin page-title="Quotations" title="Quotations">
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.quotation-templates.index') }}" class="text-sm font-medium text-slate-400 hover:text-slate-600">Manage Templates &rarr;</a>
    </div>

    <livewire:admin.quotations.table />
</x-layouts.admin>
