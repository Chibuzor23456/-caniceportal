<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\ActivityLog;
use App\Models\Invoice;

/**
 * Runs daily (see routes/console.php): flips Sent invoices past their due
 * date to Overdue, mirroring ExpireQuotationsAction's registration pattern.
 */
class OverdueInvoicesAction
{
    public function handle(): void
    {
        $overdue = Invoice::query()
            ->where('status', InvoiceStatus::Sent)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($overdue as $invoice) {
            $invoice->forceFill(['status' => InvoiceStatus::Overdue])->save();

            ActivityLog::record(
                action: 'invoice.overdue',
                description: "Invoice {$invoice->reference} is now overdue.",
                subject: $invoice,
                client: $invoice->client,
            );
        }
    }
}
