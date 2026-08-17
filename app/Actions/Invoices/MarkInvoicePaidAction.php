<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Mail\InvoicePaidMail;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\Mail;

class MarkInvoicePaidAction
{
    public function handle(Invoice $invoice): Invoice
    {
        $invoice->forceFill([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ])->save();

        ActivityLog::record(
            action: 'invoice.paid',
            description: "Invoice {$invoice->reference} was marked paid.",
            subject: $invoice,
            client: $invoice->client,
        );

        Mail::to($invoice->client->email)->queue(new InvoicePaidMail($invoice));

        $invoice->client->user?->notify(new GenericNotification(
            title: 'Payment confirmed',
            body: "We've confirmed your payment for invoice {$invoice->reference}.",
            url: route('client.invoices.show', $invoice),
            type: 'invoice',
        ));

        return $invoice;
    }
}
