<x-layouts.client page-title="Messages" title="Messages">
    <p class="mb-4 text-sm text-slate-500">We typically respond within 24 hours.</p>

    <livewire:messages.thread :client="$client" role="client" />
</x-layouts.client>
