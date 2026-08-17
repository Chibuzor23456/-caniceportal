<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PhaseStatus;
use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quotation;
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

        // So nothing sits waiting on Canice without him noticing (Section 8).
        $phasesAwaitingReview = ProjectPhase::whereIn('status', [PhaseStatus::PendingReview, PhaseStatus::InDiscussion])->count();

        $pendingInvoices = Invoice::whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])->count();
        $totalRevenue = Invoice::where('status', InvoiceStatus::Paid)->sum('amount');
        $paidInvoices = Invoice::where('status', InvoiceStatus::Paid)->count();
        $overdueInvoices = Invoice::where('status', InvoiceStatus::Overdue)->count();

        return view('admin.dashboard', [
            'totalClients' => $totalClients,
            'clientsDelta' => $delta,
            'recentActivity' => $recentActivity,
            'pendingQuotations' => $pendingQuotations,
            'expiringQuotations' => $expiringQuotations,
            'quotationActivity' => $quotationActivity,
            'activeProjects' => $activeProjects,
            'phasesAwaitingReview' => $phasesAwaitingReview,
            'pendingInvoices' => $pendingInvoices,
            'totalRevenue' => $totalRevenue,
            'currency' => CompanySetting::current()->default_currency,
            'paidInvoices' => $paidInvoices,
            'overdueInvoices' => $overdueInvoices,
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
}
