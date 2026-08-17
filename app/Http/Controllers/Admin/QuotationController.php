<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Quotations\SendQuotationAction;
use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationVersion;
use App\Services\QuotationPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function index(): View
    {
        return view('admin.quotations.index');
    }

    public function create(): View
    {
        return view('admin.quotations.create');
    }

    public function edit(Quotation $quotation): View
    {
        return view('admin.quotations.edit', ['quotation' => $quotation]);
    }

    public function show(Quotation $quotation, QuotationPdfService $pdf): View
    {
        $quotation->load('sections', 'lineItems', 'paymentPhases', 'signature', 'client', 'versions', 'project');

        return view('admin.quotations.show', [
            'quotation' => $quotation,
            'pdfUrl' => in_array($quotation->status->value, ['sent', 'viewed', 'accepted'], true) ? $pdf->temporaryUrl($quotation) : null,
        ]);
    }

    public function send(Quotation $quotation, SendQuotationAction $action): RedirectResponse
    {
        abort_if($quotation->sections()->doesntExist(), 422, 'Add at least one section before sending.');

        $action->handle($quotation);

        return redirect()->route('admin.quotations.show', $quotation)->with('status', 'Quotation sent.');
    }

    public function showVersion(Quotation $quotation, QuotationVersion $version): View
    {
        abort_unless($version->quotation_id === $quotation->id, 404);

        return view('admin.quotations.version', [
            'quotation' => $quotation,
            'version' => $version,
        ]);
    }
}
