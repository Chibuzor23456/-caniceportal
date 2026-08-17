<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceSentMail;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Notifications\GenericNotification;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Mail;

class SendInvoiceAction
{
    public function __construct(private InvoicePdfService $pdf) {}

    public function handle(Invoice $invoice): Invoice
    {
        $invoice->forceFill([
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
            'due_date' => $invoice->due_date ?? now()->addDays(7)->toDateString(),
        ])->save();

        $this->pdf->generate($invoice);

        ActivityLog::record(
            action: 'invoice.sent',
            description: "Invoice {$invoice->reference} was sent to {$invoice->client->company_name}.",
            subject: $invoice,
            client: $invoice->client,
        );

        Mail::to($invoice->client->email)->queue(new InvoiceSentMail($invoice));

        // Section 14 lists "Invoice Created" as its own trigger, but a Draft
        // never reaches the client - nothing meaningful happens until Sent,
        // so that's the point this notification actually fires at.
        $invoice->client->user?->notify(new GenericNotification(
            title: 'New invoice',
            body: "Invoice {$invoice->reference} ({$invoice->currency} ".number_format($invoice->amount, 2).') is ready.',
            url: route('client.invoices.show', $invoice),
        ));

        return $invoice;
    }
}
