<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Renders and stores the quotation PDF (Section 10). Pure-PHP DomPDF, no
 * server binaries required, safe on Hostinger shared hosting.
 */
class QuotationPdfService
{
    public function generate(Quotation $quotation): string
    {
        $quotation->loadMissing('sections', 'lineItems', 'paymentPhases', 'client', 'signature');

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'company' => CompanySetting::current(),
            'verifyUrl' => route('quotation.verify', $quotation->reference),
        ])
            ->setPaper('a4')
            // Scoped to this one template (ours, not user-uploaded) purely
            // for the page_text() page-number API; DomPDF's CSS paged-media
            // @bottom-center support isn't reliable enough to depend on.
            ->setOption('isPhpEnabled', true);

        $path = $this->path($quotation);

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    public function path(Quotation $quotation): string
    {
        return "quotations/{$quotation->reference}.pdf";
    }

    /**
     * Returns null (rather than throwing) whenever storage is unreachable or
     * the file isn't there yet, so a storage hiccup degrades a page to "PDF
     * not available yet" instead of a 500.
     */
    public function temporaryUrl(Quotation $quotation): ?string
    {
        $path = $this->path($quotation);

        try {
            if (! Storage::disk('local')->exists($path)) {
                return null;
            }

            return URL::temporarySignedRoute('storage.local', now()->addMinutes(30), ['path' => $path]);
        } catch (\Throwable) {
            return null;
        }
    }
}
