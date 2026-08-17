<x-layouts.bare :title="$quotation->reference.' - Canice Technologies'">
    <div class="flex items-center justify-between px-4 pt-8 sm:px-8">
        <img src="{{ asset('images/brand/logo-full.png') }}" alt="Canice Technologies" class="h-8 w-auto">
        <a href="{{ auth()->user()->isAdmin() ? route('admin.quotations.show', $quotation) : route('client.dashboard') }}" class="text-sm font-medium text-slate-400 hover:text-slate-600">
            &larr; Back
        </a>
    </div>

    <x-quotations.status-content :quotation="$quotation" :pdf-url="$pdfUrl" />
</x-layouts.bare>
