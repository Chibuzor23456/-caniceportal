<?php

namespace App\Http\Controllers\Client;

use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PhaseStatus;
use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientFile;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $client = $request->user()->client;

        $pendingQuotations = $client
            ? Quotation::where('client_id', $client->id)->whereIn('status', [QuotationStatus::Sent, QuotationStatus::Viewed])->get()
            : collect();

        $activeProjects = $client
            ? Project::where('client_id', $client->id)->where('status', ProjectStatus::Active)->count()
            : 0;

        // The project the client is most likely checking in on right now:
        // whichever is active most recently, falling back to their newest
        // project overall if nothing is currently active.
        $currentProject = $client
            ? Project::where('client_id', $client->id)
                ->with('phases')
                ->orderByRaw("status = 'active' desc")
                ->latest('created_at')
                ->first()
            : null;

        $currentPhase = $currentProject?->currentPhase();

        $outstandingInvoices = $client
            ? Invoice::where('client_id', $client->id)->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])->count()
            : 0;

        $pendingTestimonial = $client
            ? Testimonial::where('client_id', $client->id)->whereNull('submitted_at')->with('project')->latest('created_at')->first()
            : null;

        return view('client.dashboard', [
            'client' => $client,
            'pendingQuotations' => $pendingQuotations,
            'activeProjects' => $activeProjects,
            'currentProject' => $currentProject,
            'currentProgress' => $currentProject?->progressPercentage(),
            'currentPhaseStatus' => $currentPhase?->status->label(),
            'progressTrend' => $this->progressTrend($currentProject),
            'outstandingInvoices' => $outstandingInvoices,
            'pendingTestimonial' => $pendingTestimonial,
            'phaseBreakdown' => $this->phaseBreakdown($currentProject),
            'paymentHistory' => $client ? Invoice::where('client_id', $client->id)->latest('issue_date')->limit(5)->get() : collect(),
            'quotationHistory' => $client ? Quotation::where('client_id', $client->id)->latest('issue_date')->limit(5)->get() : collect(),
            'contracts' => $client ? Contract::where('client_id', $client->id)->latest('created_at')->limit(5)->get() : collect(),
            'filesByCategory' => $client
                ? ClientFile::where('client_id', $client->id)->get()->groupBy(fn (ClientFile $file) => $file->category->label())->map->count()
                : collect(),
            'filesCount' => $client ? ClientFile::where('client_id', $client->id)->count() : 0,
            'averageResponseMinutes' => $client ? $this->averageResponseMinutes($client) : null,
            'insights' => $client ? $this->smartInsights($client, $pendingQuotations->count(), $outstandingInvoices) : [],
        ]);
    }

    private function progressTrend(?Project $project): ?array
    {
        if (! $project || $project->phases->isEmpty()) {
            return null;
        }

        $total = $project->phases->count();

        $points = $project->phases
            ->where('status', PhaseStatus::Approved)
            ->sortBy('approved_at')
            ->values()
            ->map(fn ($phase, $index) => [
                'label' => $phase->approved_at->format('M j'),
                'percentage' => (int) round((($index + 1) / $total) * 100),
            ])
            ->all();

        return [
            'points' => array_merge([['label' => 'Start', 'percentage' => 0]], $points),
        ];
    }

    private function phaseBreakdown(?Project $project): array
    {
        if (! $project || $project->phases->isEmpty()) {
            return [];
        }

        $colors = [
            PhaseStatus::NotStarted->value => '#e2e8f0',
            PhaseStatus::PendingReview->value => '#f97316',
            PhaseStatus::InDiscussion->value => '#a855f7',
            PhaseStatus::Approved->value => '#10b981',
        ];

        return collect(PhaseStatus::cases())
            ->map(fn (PhaseStatus $status) => [
                'label' => $status->label(),
                'value' => $project->phases->where('status', $status)->count(),
                'color' => $colors[$status->value] ?? '#94a3b8',
            ])
            ->filter(fn ($segment) => $segment['value'] > 0)
            ->values()
            ->all();
    }

    /**
     * Pairs the first client message in a waiting streak with the first
     * admin reply that follows it - resets after every admin reply.
     */
    private function averageResponseMinutes(Client $client): ?int
    {
        $messages = Message::where('client_id', $client->id)->orderBy('created_at')->with('sender')->get();

        $diffs = [];
        $waitingSince = null;

        foreach ($messages as $message) {
            $isAdmin = $message->sender->isAdmin();

            if (! $isAdmin && $waitingSince === null) {
                $waitingSince = $message->created_at;
            } elseif ($isAdmin && $waitingSince !== null) {
                $diffs[] = $waitingSince->diffInMinutes($message->created_at);
                $waitingSince = null;
            }
        }

        return count($diffs) > 0 ? (int) round(array_sum($diffs) / count($diffs)) : null;
    }

    private function smartInsights(Client $client, int $pendingQuotationsCount, int $outstandingInvoicesCount): array
    {
        $unreadMessages = Message::where('client_id', $client->id)->whereNull('read_at')->whereHas('sender', fn ($q) => $q->where('role', 'admin'))->count();
        $pendingContracts = Contract::where('client_id', $client->id)->whereIn('status', [ContractStatus::Sent, ContractStatus::Viewed])->count();

        $insights = [];

        if ($pendingQuotationsCount > 0) {
            $insights[] = "{$pendingQuotationsCount} quotation".($pendingQuotationsCount === 1 ? '' : 's').' awaiting your decision.';
        }

        if ($pendingContracts > 0) {
            $insights[] = "{$pendingContracts} contract".($pendingContracts === 1 ? '' : 's').' awaiting your signature.';
        }

        if ($outstandingInvoicesCount > 0) {
            $insights[] = "{$outstandingInvoicesCount} invoice".($outstandingInvoicesCount === 1 ? '' : 's').' outstanding.';
        }

        if ($unreadMessages > 0) {
            $insights[] = "{$unreadMessages} unread message".($unreadMessages === 1 ? '' : 's').' from Canice Technologies.';
        }

        return $insights;
    }
}
