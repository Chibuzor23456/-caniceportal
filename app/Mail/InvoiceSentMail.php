<?php

namespace App\Mail;

use App\Models\Invoice;

class InvoiceSentMail extends TemplatedMail
{
    public function __construct(public Invoice $invoice) {}

    protected function type(): string
    {
        return 'invoice_sent';
    }

    protected function fallbackSubject(): string
    {
        return "New Invoice from Canice Technologies ({$this->invoice->reference})";
    }

    protected function mailView(): string
    {
        return 'emails.invoices.sent';
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
