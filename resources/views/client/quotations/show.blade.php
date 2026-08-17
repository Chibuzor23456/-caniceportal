<x-layouts.client :page-title="$quotation->reference" :title="$quotation->reference">
    <div class="-mx-4 sm:-mx-8">
        <x-quotations.status-content :quotation="$quotation" :pdf-url="$pdfUrl" />
    </div>
</x-layouts.client>
