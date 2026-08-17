<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Renders and stores the invoice PDF (Section 14). Same DomPDF/R2 approach
 * as QuotationPdfService, but the document itself is intentionally shorter.
 */
class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing('client');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => CompanySetting::current(),
        ])
            ->setPaper('a4')
            // Scoped to this one template (ours, not user-authored) purely
            // for the page_text() page-number API.
            ->setOption('isPhpEnabled', true);

        $path = $this->path($invoice);

        Storage::disk('r2')->put($path, $pdf->output());

        return $path;
    }

    public function path(Invoice $invoice): string
    {
        return "invoices/{$invoice->reference}.pdf";
    }

    public function temporaryUrl(Invoice $invoice): ?string
    {
        $path = $this->path($invoice);

        try {
            if (! Storage::disk('r2')->exists($path)) {
                return null;
            }

            return Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(30));
        } catch (\Throwable) {
            return null;
        }
    }
}
