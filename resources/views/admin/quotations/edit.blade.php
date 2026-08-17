<x-layouts.admin :page-title="'Edit '.$quotation->reference" :title="'Edit '.$quotation->reference">
    <livewire:admin.quotations.builder :quotation="$quotation" :key="'builder-'.$quotation->id" />
</x-layouts.admin>
