<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Project;

class CreateInvoiceAction
{
    /**
     * Pre-fills from the next unbilled phase of the project's quotation
     * Payment Schedule (Section 14) - the earliest-order phase that doesn't
     * already have a non-cancelled invoice. Falls back to a blank draft if
     * the quotation has no structured payment phases at all.
     */
    public function handle(Project $project): Invoice
    {
        $phase = $project->quotation->paymentPhases()
            ->whereDoesntHave('invoices', fn ($q) => $q->where('status', '!=', InvoiceStatus::Cancelled->value))
            ->orderBy('order')
            ->first();

        return Invoice::create([
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'quotation_payment_phase_id' => $phase?->id,
            'created_by' => auth()->id(),
            'reference' => $this->nextReference(),
            'status' => InvoiceStatus::Draft,
            'description' => $phase?->description ?? '',
            'amount' => $phase?->amount ?? 0,
            'currency' => $project->quotation->currency,
            'issue_date' => now()->toDateString(),
        ]);
    }

    private function nextReference(): string
    {
        $year = now()->year;

        do {
            $count = Invoice::withTrashed()->whereYear('created_at', $year)->count() + 1;
            $reference = sprintf('INV-%d-%04d', $year, $count);
            $exists = Invoice::withTrashed()->where('reference', $reference)->exists();
        } while ($exists);

        return $reference;
    }
}
