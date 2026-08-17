<?php

namespace App\Mail;

use App\Models\Quotation;

class QuotationSentMail extends TemplatedMail
{
    public function __construct(public Quotation $quotation) {}

    protected function type(): string
    {
        return 'quotation_sent';
    }

    protected function fallbackSubject(): string
    {
        return "New Quotation from Canice Technologies ({$this->quotation->reference})";
    }

    protected function mailView(): string
    {
        return 'emails.quotations.sent';
    }

    protected function viewData(): array
    {
        return [
            'quotation' => $this->quotation,
            'secureUrl' => route('quotation.secure', $this->quotation->secure_token),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->quotation->reference,
            'client_name' => $this->quotation->client->company_name,
            'secure_url' => route('quotation.secure', $this->quotation->secure_token),
        ];
    }
}
