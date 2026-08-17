@props(['title' => null, 'pageTitle' => null, 'freshness' => null])
@php
    use App\Enums\ContractStatus;
    use App\Enums\InvoiceStatus;
    use App\Enums\PhaseStatus;
    use App\Enums\QuotationStatus;
    use App\Models\Client;
    use App\Models\Contract;
    use App\Models\Invoice;
    use App\Models\Message;
    use App\Models\ProjectPhase;
    use App\Models\Quotation;

    $activeClients = Client::where('status', \App\Enums\ClientStatus::Active)->count();
    $pendingQuotations = Quotation::whereIn('status', [QuotationStatus::Sent, QuotationStatus::Viewed])->count();
    $phasesAwaitingReview = ProjectPhase::whereIn('status', [PhaseStatus::PendingReview, PhaseStatus::InDiscussion])->count();
    $pendingInvoices = Invoice::whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])->count();
    $unreadMessages = Message::whereNull('read_at')->whereHas('sender', fn ($q) => $q->where('role', 'client'))->count();
    $pendingContracts = Contract::whereIn('status', [ContractStatus::Sent, ContractStatus::Viewed])->count();

    $navGroups = [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Overview', 'icon' => 'overview', 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'badge' => null],
                ['label' => 'Clients', 'icon' => 'clients', 'url' => route('admin.clients.index'), 'active' => request()->routeIs('admin.clients.*'), 'badge' => $activeClients],
                ['label' => 'Quotations', 'icon' => 'quotations', 'url' => route('admin.quotations.index'), 'active' => request()->routeIs('admin.quotations.*', 'admin.quotation-templates.*'), 'badge' => $pendingQuotations ?: null],
                ['label' => 'Projects', 'icon' => 'projects', 'url' => route('admin.projects.index'), 'active' => request()->routeIs('admin.projects.*'), 'badge' => $phasesAwaitingReview ?: null],
                ['label' => 'Invoices', 'icon' => 'invoices', 'url' => route('admin.invoices.index'), 'active' => request()->routeIs('admin.invoices.*'), 'badge' => $pendingInvoices ?: null],
            ],
        ],
        [
            'label' => 'Manage',
            'items' => [
                ['label' => 'Contracts', 'icon' => 'contracts', 'url' => route('admin.contracts.index'), 'active' => request()->routeIs('admin.contracts.*'), 'badge' => $pendingContracts ?: null],
                ['label' => 'Files', 'icon' => 'files', 'url' => route('admin.files.index'), 'active' => request()->routeIs('admin.files.*'), 'badge' => null],
                ['label' => 'Messages', 'icon' => 'messages', 'url' => route('admin.messages.index'), 'active' => request()->routeIs('admin.messages.*'), 'badge' => $unreadMessages ?: null],
                ['label' => 'Activity Log', 'icon' => 'activity', 'url' => route('admin.activity.index'), 'active' => request()->routeIs('admin.activity.*'), 'badge' => null],
                ['label' => 'Settings', 'icon' => 'settings', 'url' => route('admin.settings.company'), 'active' => request()->routeIs('admin.settings.*'), 'badge' => null],
            ],
        ],
    ];
@endphp

<x-layouts.app
    :title="$title"
    workspace-name="Canice Technologies"
    :workspace-subtitle="$activeClients.' active clients'"
    :nav-groups="$navGroups"
    :logout-route="route('admin.logout')"
    :page-title="$pageTitle"
    :freshness="$freshness"
>
    {{ $slot }}
</x-layouts.app>
