<?php

namespace App\Http\Controllers\Client;

use App\Actions\Invoices\UploadPaymentProofAction;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->where('client_id', $request->user()->client?->id)
            ->whereNotIn('status', [InvoiceStatus::Draft])
            ->latest('created_at')
            ->get();

        return view('client.invoices.index', ['invoices' => $invoices]);
    }

    public function show(Invoice $invoice, Request $request, InvoicePdfService $pdf): View
    {
        $this->authorize('view', $invoice);
        abort_if($invoice->status === InvoiceStatus::Draft, 404);

        $invoice->load('client', 'project');

        return view('client.invoices.show', [
            'invoice' => $invoice,
            'pdfUrl' => $pdf->temporaryUrl($invoice),
        ]);
    }

    public function uploadProof(Invoice $invoice, Request $request, UploadPaymentProofAction $action): RedirectResponse
    {
        $this->authorize('uploadProof', $invoice);

        $data = $request->validate([
            'proof' => ['required', 'file', 'max:10240'],
        ]);

        $action->handle($invoice, $data['proof']);

        return redirect()->route('client.invoices.show', $invoice)->with('status', 'Payment proof uploaded. We will verify and confirm shortly.');
    }
}
