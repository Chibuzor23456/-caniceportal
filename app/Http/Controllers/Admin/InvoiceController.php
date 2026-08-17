<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Invoices\CreateInvoiceAction;
use App\Actions\Invoices\MarkInvoicePaidAction;
use App\Actions\Invoices\SendInvoiceAction;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\InvoicePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);

        return view('admin.invoices.index');
    }

    public function show(Invoice $invoice, InvoicePdfService $pdf): View
    {
        $this->authorize('view', $invoice);

        $invoice->load('client', 'project', 'paymentPhase');

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'pdfUrl' => $invoice->status !== InvoiceStatus::Draft ? $pdf->temporaryUrl($invoice) : null,
        ]);
    }

    public function store(Project $project, CreateInvoiceAction $action): RedirectResponse
    {
        $this->authorize('view', $project);

        $invoice = $action->handle($project);

        return redirect()->route('admin.invoices.show', $invoice)->with('status', 'Draft invoice created.');
    }

    public function update(Invoice $invoice, Request $request): RedirectResponse
    {
        $this->authorize('view', $invoice);

        abort_unless($invoice->status === InvoiceStatus::Draft, 422, 'Only draft invoices can be edited.');

        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $invoice->update($data);

        return redirect()->route('admin.invoices.show', $invoice)->with('status', 'Invoice updated.');
    }

    public function send(Invoice $invoice, SendInvoiceAction $action): RedirectResponse
    {
        $this->authorize('view', $invoice);

        abort_unless($invoice->status === InvoiceStatus::Draft, 422, 'Only draft invoices can be sent.');

        $action->handle($invoice);

        return redirect()->route('admin.invoices.show', $invoice)->with('status', 'Invoice sent.');
    }

    public function markPaid(Invoice $invoice, MarkInvoicePaidAction $action): RedirectResponse
    {
        $this->authorize('markPaid', $invoice);

        $action->handle($invoice);

        return redirect()->route('admin.invoices.show', $invoice)->with('status', 'Invoice marked paid.');
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);

        abort_if(in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Cancelled], true), 422, 'This invoice can no longer be cancelled.');

        $invoice->forceFill(['status' => InvoiceStatus::Cancelled])->save();

        ActivityLog::record(
            action: 'invoice.cancelled',
            description: "Invoice {$invoice->reference} was cancelled.",
            subject: $invoice,
            client: $invoice->client,
        );

        return redirect()->route('admin.invoices.show', $invoice)->with('status', 'Invoice cancelled.');
    }

    public function export(): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::with('client')->latest('created_at')->get();

        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['Reference', 'Client', 'Status', 'Description', 'Amount', 'Currency', 'Issue Date', 'Due Date', 'Paid At']);

        foreach ($invoices as $invoice) {
            fputcsv($csv, [
                $invoice->reference,
                $invoice->client->company_name,
                $invoice->status->label(),
                $invoice->description,
                $invoice->amount,
                $invoice->currency,
                $invoice->issue_date?->format('Y-m-d'),
                $invoice->due_date?->format('Y-m-d'),
                $invoice->paid_at?->format('Y-m-d'),
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="invoices.csv"',
        ]);
    }
}
