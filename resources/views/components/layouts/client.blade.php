@props(['title' => null, 'pageTitle' => null, 'freshness' => null])
@php
    use App\Enums\ContractStatus;
    use App\Enums\InvoiceStatus;
    use App\Enums\ProjectStatus;
    use App\Enums\QuotationStatus;
    use App\Models\Contract;
    use App\Models\Invoice;
    use App\Models\Message;
    use App\Models\Project;
    use App\Models\Quotation;

    $client = auth()->user()->client;
    $pendingQuotations = $client
        ? Quotation::where('client_id', $client->id)->whereIn('status', [QuotationStatus::Sent, QuotationStatus::Viewed])->count()
        : 0;
    $activeProjects = $client
        ? Project::where('client_id', $client->id)->where('status', ProjectStatus::Active)->count()
        : 0;
    $outstandingInvoices = $client
        ? Invoice::where('client_id', $client->id)->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])->count()
        : 0;
    $unreadMessages = $client
        ? Message::where('client_id', $client->id)->whereNull('read_at')->whereHas('sender', fn ($q) => $q->where('role', 'admin'))->count()
        : 0;
    $pendingContracts = $client
        ? Contract::where('client_id', $client->id)->whereIn('status', [ContractStatus::Sent, ContractStatus::Viewed])->count()
        : 0;

    $navGroups = [
        [
            'label' => 'Overview',
            'items' => [
                ['label' => 'Overview', 'icon' => 'overview', 'url' => route('client.dashboard'), 'active' => request()->routeIs('client.dashboard'), 'badge' => null],
            ],
        ],
        [
            'label' => 'My Work',
            'items' => [
                ['label' => 'My Projects', 'icon' => 'projects', 'url' => route('client.projects.index'), 'active' => request()->routeIs('client.projects.*'), 'badge' => $activeProjects ?: null],
                ['label' => 'Quotations', 'icon' => 'quotations', 'url' => route('client.quotations.index'), 'active' => request()->routeIs('client.quotations.*'), 'badge' => $pendingQuotations ?: null],
                ['label' => 'Contracts', 'icon' => 'contracts', 'url' => route('client.contracts.index'), 'active' => request()->routeIs('client.contracts.*'), 'badge' => $pendingContracts ?: null],
                ['label' => 'Documents', 'icon' => 'documents', 'url' => route('client.coming-soon', ['any' => 'documents']), 'active' => request()->is('app/documents*'), 'badge' => null],
                ['label' => 'Invoices', 'icon' => 'invoices', 'url' => route('client.invoices.index'), 'active' => request()->routeIs('client.invoices.*'), 'badge' => $outstandingInvoices ?: null],
                ['label' => 'Messages', 'icon' => 'messages', 'url' => route('client.messages.index'), 'active' => request()->routeIs('client.messages.*'), 'badge' => $unreadMessages ?: null],
                ['label' => 'Activity', 'icon' => 'activity', 'url' => route('client.activity.index'), 'active' => request()->routeIs('client.activity.*'), 'badge' => null],
            ],
        ],
    ];
@endphp

<x-layouts.app
    :title="$title"
    :workspace-name="$client?->company_name ?? 'Your Workspace'"
    workspace-subtitle="Client Portal"
    :nav-groups="$navGroups"
    :logout-route="route('logout')"
    :page-title="$pageTitle"
    :freshness="$freshness"
>
    {{ $slot }}
</x-layouts.app>
