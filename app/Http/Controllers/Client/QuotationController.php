<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\QuotationPdfService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function index(Request $request): View
    {
        $quotations = Quotation::query()
            ->where('client_id', $request->user()->client?->id)
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->get();

        return view('client.quotations.index', ['quotations' => $quotations]);
    }

    public function show(Quotation $quotation, Request $request, QuotationPdfService $pdf): View
    {
        abort_unless($quotation->client_id === $request->user()->client?->id, 404);

        $quotation->load('sections', 'lineItems', 'paymentPhases', 'signature', 'client');

        return view('client.quotations.show', [
            'quotation' => $quotation,
            'pdfUrl' => $quotation->status->value === 'accepted' ? $pdf->temporaryUrl($quotation) : null,
        ]);
    }
}
