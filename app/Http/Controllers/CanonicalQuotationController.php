<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Services\QuotationPdfService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CanonicalQuotationController extends Controller
{
    public function __invoke(string $slug, Request $request, QuotationPdfService $pdf): View
    {
        $quotation = Quotation::query()
            ->where('slug', $slug)
            ->with(['sections', 'lineItems', 'paymentPhases', 'signature', 'client'])
            ->firstOrFail();

        $user = $request->user();
        $allowed = $user->isAdmin() || ($user->isClient() && $user->client?->id === $quotation->client_id);

        if (! $allowed) {
            throw new AccessDeniedHttpException;
        }

        return view('quotations.canonical', [
            'quotation' => $quotation,
            'pdfUrl' => $quotation->status->value === 'accepted' ? $pdf->temporaryUrl($quotation) : null,
        ]);
    }
}
