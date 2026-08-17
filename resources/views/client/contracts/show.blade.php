<x-layouts.client :page-title="$contract->reference" :title="$contract->reference">
    <div class="-mx-4 sm:-mx-8">
        <x-contracts.status-content :contract="$contract" :pdf-url="$pdfUrl" />
    </div>
</x-layouts.client>
