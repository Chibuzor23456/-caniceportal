<x-layouts.bare :title="$contract->reference.' - Canice Technologies'">
    <div class="flex justify-center px-4 pt-8">
        <img src="{{ asset('images/brand/logo-full.png') }}" alt="Canice Technologies" class="h-8 w-auto">
    </div>

    <x-contracts.status-content
        :contract="$contract"
        :pdf-url="app(\App\Services\ContractPdfService::class)->temporaryUrl($contract)"
    />
</x-layouts.bare>
