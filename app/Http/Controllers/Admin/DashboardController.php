<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatus;
use App\Enums\EmailStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PhaseStatus;
use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientFile;
use App\Models\CompanySetting;
use App\Models\Contract;
use App\Models\EmailLog;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quotation;
use App\Models\QuotationLineItem;
use App\Models\Testimonial;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalClients = Client::count();

        $clientsThisWeek = Client::where('created_at', '>=', now()->subDays(7))->count();
        $clientsPriorWeek = Client::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();

        $delta = $clientsPriorWeek > 0
            ? round((($clientsThisWeek - $clientsPriorWeek) / $clientsPriorWeek) * 100)
            : ($clientsThisWeek > 0 ? 100 : 0);

        $recentActivity = ActivityLog::with('causer')->latest('created_at')->limit(8)->get();

        $pendingQuotations = Quotation::whereIn('status', [QuotationStatus::Sent, QuotationStatus::Viewed])->count();

        $expiringQuotations = Quotation::whereIn('status', [QuotationStatus::Sent, QuotationStatus::Viewed])
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count();

        $quotationActivity = $this->quotationActivityByWeek();

        $activeProjects = Project::where('status', ProjectStatus::Active)->count();
        $completedProjects = Project::where('status', ProjectStatus::Completed)->count();

        // So nothing sits waiting on Canice without him noticing (Section 8).
        $phasesAwaitingReview = ProjectPhase::whereIn('status', [PhaseStatus::PendingReview, PhaseStatus::InDiscussion])->count();

        $pendingInvoices = Invoice::whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])->count();
        $totalRevenue = Invoice::where('status', InvoiceStatus::Paid)->sum('amount');
        $paidInvoices = Invoice::where('status', InvoiceStatus::Paid)->count();
        $overdueInvoices = Invoice::where('status', InvoiceStatus::Overdue)->count();

        $filesUploaded = ClientFile::count();

        $sentQuotations = Quotation::whereNotNull('sent_at')->count();
        $acceptedQuotations = Quotation::where('status', QuotationStatus::Accepted)->count();
        $conversionRate = $sentQuotations > 0 ? round(($acceptedQuotations / $sentQuotations) * 100) : 0;

        $invoicedTotal = Invoice::whereIn('status', [InvoiceStatus::Paid, InvoiceStatus::Sent, InvoiceStatus::Overdue])->sum('amount');
        $paymentCollectionRate = $invoicedTotal > 0 ? round(($totalRevenue / $invoicedTotal) * 100) : 0;

        return view('admin.dashboard', [
            'totalClients' => $totalClients,
            'clientsDelta' => $delta,
            'recentActivity' => $recentActivity,
            'pendingQuotations' => $pendingQuotations,
            'expiringQuotations' => $expiringQuotations,
            'quotationActivity' => $quotationActivity,
            'activeProjects' => $activeProjects,
            'completedProjects' => $completedProjects,
            'phasesAwaitingReview' => $phasesAwaitingReview,
            'pendingInvoices' => $pendingInvoices,
            'totalRevenue' => $totalRevenue,
            'currency' => CompanySetting::current()->default_currency,
            'paidInvoices' => $paidInvoices,
            'overdueInvoices' => $overdueInvoices,
            'filesUploaded' => $filesUploaded,
            'conversionRate' => $conversionRate,
            'paymentCollectionRate' => $paymentCollectionRate,
            'revenueByWeek' => $this->revenueByWeek(),
            'topClientsByRevenue' => $this->topClientsByRevenue(),
            'projectsByStatus' => $this->projectsByStatus(),
            'clientGrowth' => $this->clientGrowthByMonth(),
            'revenueByService' => $this->revenueByService(),
            'recentMessages' => Message::with(['client', 'sender'])->latest('created_at')->limit(5)->get(),
            'emailHealth' => $this->emailHealth(),
            'insights' => $this->smartInsights($expiringQuotations, $overdueInvoices, $phasesAwaitingReview),
        ]);
    }

    private function quotationActivityByWeek(): array
    {
        $weeks = collect(range(5, 0))->map(function (int $weeksAgo) {
            $start = now()->subWeeks($weeksAgo)->startOfWeek();
            $end = now()->subWeeks($weeksAgo)->endOfWeek();

            return [
                'label' => $start->format('M j'),
                'sent' => Quotation::whereBetween('sent_at', [$start, $end])->count(),
                'accepted' => Quotation::whereBetween('accepted_at', [$start, $end])->count(),
            ];
        });

        $currentTotal = $weeks->last()['sent'] + $weeks->last()['accepted'];
        $priorTotal = $weeks->slice(-2, 1)->first()['sent'] + $weeks->slice(-2, 1)->first()['accepted'];

        $delta = $priorTotal > 0
            ? round((($currentTotal - $priorTotal) / $priorTotal) * 100)
            : ($currentTotal > 0 ? 100 : 0);

        return [
            'weeks' => $weeks->all(),
            'currentTotal' => $currentTotal,
            'delta' => $delta,
            'max' => max(1, $weeks->flatMap(fn ($w) => [$w['sent'], $w['accepted']])->max()),
        ];
    }

    private function revenueByWeek(): array
    {
        $weeks = collect(range(5, 0))->map(function (int $weeksAgo) {
            $start = now()->subWeeks($weeksAgo)->startOfWeek();
            $end = now()->subWeeks($weeksAgo)->endOfWeek();

            return [
                'label' => $start->format('M j'),
                'amount' => (float) Invoice::where('status', InvoiceStatus::Paid)->whereBetween('paid_at', [$start, $end])->sum('amount'),
            ];
        });

        return [
            'weeks' => $weeks->all(),
            'max' => max(1, $weeks->max('amount')),
        ];
    }

    private function topClientsByRevenue(): Collection
    {
        return Client::query()
            ->select('clients.*')
            ->selectRaw('COALESCE(SUM(invoices.amount), 0) as revenue')
            ->leftJoin('invoices', function ($join) {
                $join->on('invoices.client_id', '=', 'clients.id')->where('invoices.status', InvoiceStatus::Paid->value);
            })
            ->groupBy('clients.id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->filter(fn ($client) => $client->revenue > 0)
            ->values();
    }

    private function projectsByStatus(): array
    {
        $colors = [
            ProjectStatus::Active->value => '#3b82f6',
            ProjectStatus::Paused->value => '#f97316',
            ProjectStatus::Completed->value => '#10b981',
            ProjectStatus::Cancelled->value => '#ef4444',
        ];

        return collect(ProjectStatus::cases())
            ->map(fn (ProjectStatus $status) => [
                'label' => $status->label(),
                'value' => Project::where('status', $status)->count(),
                'color' => $colors[$status->value],
            ])
            ->filter(fn ($segment) => $segment['value'] > 0)
            ->values()
            ->all();
    }

    private function clientGrowthByMonth(): array
    {
        $months = collect(range(5, 0))->map(function (int $monthsAgo) {
            $month = now()->subMonths($monthsAgo);

            return [
                'label' => $month->format('M'),
                'count' => Client::whereYear('created_at', $month->year)->whereMonth('created_at', $month->month)->count(),
            ];
        });

        return [
            'months' => $months->all(),
            'max' => max(1, $months->max('count')),
        ];
    }

    private function revenueByService(): array
    {
        $totals = QuotationLineItem::query()
            ->join('quotations', 'quotations.id', '=', 'quotation_line_items.quotation_id')
            ->where('quotations.status', QuotationStatus::Accepted->value)
            ->whereNotNull('quotation_line_items.service_category')
            ->select('quotation_line_items.service_category')
            ->selectRaw('SUM(quotation_line_items.line_total) as total')
            ->groupBy('quotation_line_items.service_category')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return [
            'rows' => $totals->all(),
            'max' => max(1, (float) $totals->max('total')),
        ];
    }

    private function emailHealth(): array
    {
        $since = now()->subDays(7);

        return [
            'sent' => EmailLog::where('status', EmailStatus::Sent)->where('created_at', '>=', $since)->count(),
            'failed' => EmailLog::where('status', EmailStatus::Failed)->where('created_at', '>=', $since)->count(),
            'bounced' => EmailLog::where('status', EmailStatus::Bounced)->where('created_at', '>=', $since)->count(),
            'recentFailures' => EmailLog::whereIn('status', [EmailStatus::Failed, EmailStatus::Bounced])->latest('created_at')->limit(3)->get(),
        ];
    }

    private function smartInsights(int $expiringQuotations, int $overdueInvoices, int $phasesAwaitingReview): array
    {
        $pendingContracts = Contract::whereIn('status', [ContractStatus::Sent, ContractStatus::Viewed])->count();
        $pendingTestimonials = Testimonial::whereNull('submitted_at')->count();

        $insights = [];

        if ($expiringQuotations > 0) {
            $insights[] = "{$expiringQuotations} quotation".($expiringQuotations === 1 ? '' : 's').' expiring this week.';
        }

        if ($overdueInvoices > 0) {
            $insights[] = "{$overdueInvoices} invoice".($overdueInvoices === 1 ? '' : 's').' overdue.';
        }

        if ($phasesAwaitingReview > 0) {
            $insights[] = "{$phasesAwaitingReview} project phase".($phasesAwaitingReview === 1 ? '' : 's').' awaiting your review.';
        }

        if ($pendingContracts > 0) {
            $insights[] = "{$pendingContracts} contract".($pendingContracts === 1 ? '' : 's').' awaiting a client signature.';
        }

        if ($pendingTestimonials > 0) {
            $insights[] = "{$pendingTestimonials} client".($pendingTestimonials === 1 ? '' : 's').' yet to leave a testimonial.';
        }

        return $insights;
    }
}
