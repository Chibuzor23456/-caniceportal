<x-layouts.bare :title="$quotation->reference.' - Canice Technologies'">
    <div class="flex justify-center px-4 pt-8">
        <img src="{{ asset('images/brand/logo-full.png') }}" alt="Canice Technologies" class="h-8 w-auto">
    </div>

    <x-quotations.status-content
        :quotation="$quotation"
        :pdf-url="$quotation->status === \App\Enums\QuotationStatus::Accepted ? app(\App\Services\QuotationPdfService::class)->temporaryUrl($quotation) : null"
    />
</x-layouts.bare>
