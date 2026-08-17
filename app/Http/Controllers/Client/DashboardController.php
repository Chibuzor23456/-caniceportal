<?php

namespace App\Http\Controllers\Client;

use App\Enums\InvoiceStatus;
use App\Enums\PhaseStatus;
use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
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
}
