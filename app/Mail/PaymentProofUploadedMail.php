<?php

namespace App\Mail;

use App\Models\Invoice;

class PaymentProofUploadedMail extends TemplatedMail
{
    public function __construct(public Invoice $invoice) {}

    protected function type(): string
    {
        return 'payment_proof_uploaded';
    }

    protected function fallbackSubject(): string
    {
        return "Payment proof uploaded for {$this->invoice->reference}";
    }

    protected function mailView(): string
    {
        return 'emails.invoices.payment-proof-uploaded';
    }

    protected function viewData(): array
    {
        return [
            'invoice' => $this->invoice,
            'url' => route('admin.invoices.show', $this->invoice),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->invoice->reference,
            'client_name' => $this->invoice->client->company_name,
            'url' => route('admin.invoices.show', $this->invoice),
        ];
    }
}
