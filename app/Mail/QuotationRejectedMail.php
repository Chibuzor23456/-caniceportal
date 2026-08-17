<?php

namespace App\Mail;

use App\Models\Quotation;

class QuotationRejectedMail extends TemplatedMail
{
    public function __construct(public Quotation $quotation) {}

    protected function type(): string
    {
        return 'quotation_rejected';
    }

    protected function fallbackSubject(): string
    {
        return "Quotation {$this->quotation->reference} was declined";
    }

    protected function mailView(): string
    {
        return 'emails.quotations.rejected';
    }

    protected function viewData(): array
    {
        return [
            'quotation' => $this->quotation,
            'adminUrl' => route('admin.quotations.show', $this->quotation),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->quotation->reference,
            'client_name' => $this->quotation->client->company_name,
            'admin_url' => route('admin.quotations.show', $this->quotation),
        ];
    }
}
