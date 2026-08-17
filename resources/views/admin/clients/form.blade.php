<x-layouts.admin :page-title="$client ? 'Edit Client' : 'New Client'" :title="$client ? 'Edit Client' : 'New Client'">
    <livewire:admin.clients.form :client="$client" />
</x-layouts.admin>
