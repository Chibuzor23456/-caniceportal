<?php

namespace App\Mail;

use App\Models\Quotation;

class QuotationExpiredMail extends TemplatedMail
{
    public function __construct(
        public Quotation $quotation,
        public bool $forAdmin = false,
    ) {}

    protected function type(): string
    {
        return 'quotation_expired';
    }

    protected function fallbackSubject(): string
    {
        return "Quotation {$this->quotation->reference} has expired";
    }

    protected function mailView(): string
    {
        return 'emails.quotations.expired';
    }

    protected function viewData(): array
    {
        return [
            'quotation' => $this->quotation,
            'forAdmin' => $this->forAdmin,
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->quotation->reference,
            'client_name' => $this->quotation->client->company_name,
        ];
    }
}
