<?php

use App\Models\QuotationTemplate;
use Livewire\Component;

new class extends Component
{
    public function duplicate(int $templateId): void
    {
        $template = QuotationTemplate::with('sections', 'lineItems', 'tags')->findOrFail($templateId);

        $copy = QuotationTemplate::create([
            'created_by' => auth()->id(),
            'name' => $template->name.' (Copy)',
        ]);

        foreach ($template->sections as $section) {
            $copy->sections()->create($section->only(['type', 'title', 'body', 'order']));
        }

        foreach ($template->lineItems as $item) {
            $copy->lineItems()->create($item->only(['service_name', 'service_category', 'quantity', 'unit_price', 'discount', 'tax', 'order']));
        }

        $copy->tags()->sync($template->tags->pluck('id'));
    }

    public function delete(int $templateId): void
    {
        QuotationTemplate::findOrFail($templateId)->delete();
    }

    public function with(): array
    {
        return ['templates' => QuotationTemplate::with('tags')->withCount('sections')->latest()->get()];
    }
};
?>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-slate-100 text-xs font-semibold tracking-wider text-slate-400 uppercase">
            <tr>
                <th class="px-5 py-3">Name</th>
                <th class="px-5 py-3">Tags</th>
                <th class="px-5 py-3">Sections</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            @forelse ($templates as $template)
                <tr wire:key="template-{{ $template->id }}">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $template->name }}</td>
                    <td class="px-5 py-3">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($template->tags as $tag)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-5 py-3 text-slate-500">{{ $template->sections_count }}</td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex justify-end gap-2 text-xs">
                            <button wire:click="duplicate({{ $template->id }})" class="rounded-lg border border-slate-200 px-2.5 py-1.5 font-medium text-slate-600 hover:bg-slate-50">Duplicate</button>
                            <button wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?" class="rounded-lg bg-red-50 px-2.5 py-1.5 font-medium text-red-600 hover:bg-red-100">Delete</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">No templates saved yet. Save a quotation as a template to see it here.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
