<?php

namespace App\Mail;

use App\Models\Invoice;

class InvoicePaidMail extends TemplatedMail
{
    public function __construct(public Invoice $invoice) {}

    protected function type(): string
    {
        return 'invoice_paid';
    }

    protected function fallbackSubject(): string
    {
        return "Payment received - {$this->invoice->reference}";
    }

    protected function mailView(): string
    {
        return 'emails.invoices.paid';
    }

    protected function viewData(): array
    {
        return [
            'invoice' => $this->invoice,
            'url' => route('client.invoices.show', $this->invoice),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->invoice->reference,
            'url' => route('client.invoices.show', $this->invoice),
        ];
    }
}
