<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Only used for the body-authored path (Section 14) - an uploaded contract
 * is already a PDF/doc and is served as-is, see Contract::isUploaded().
 * Same DomPDF/DejaVu-Sans/page_text() setup as QuotationPdfService.
 */
class ContractPdfService
{
    public function generate(Contract $contract): string
    {
        $contract->loadMissing('client', 'signature');

        $pdf = Pdf::loadView('pdf.contract', [
            'contract' => $contract,
            'company' => CompanySetting::current(),
        ])
            ->setPaper('a4')
            ->setOption('isPhpEnabled', true);

        $path = $this->path($contract);

        Storage::disk('r2')->put($path, $pdf->output());

        return $path;
    }

    public function path(Contract $contract): string
    {
        return "contracts/{$contract->reference}.pdf";
    }

    public function temporaryUrl(Contract $contract): ?string
    {
        // The uploaded path is served directly - never generated, so it
        // always exists and needs no DomPDF-render fallback.
        $path = $contract->isUploaded() ? $contract->uploaded_file_path : $this->path($contract);

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
