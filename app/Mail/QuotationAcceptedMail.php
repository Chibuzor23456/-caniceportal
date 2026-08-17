<?php

namespace App\Mail;

use App\Models\Quotation;

class QuotationAcceptedMail extends TemplatedMail
{
    public function __construct(
        public Quotation $quotation,
        public bool $forAdmin = false,
    ) {}

    protected function type(): string
    {
        return 'quotation_accepted';
    }

    protected function fallbackSubject(): string
    {
        return "Quotation {$this->quotation->reference} was accepted";
    }

    protected function mailView(): string
    {
        return 'emails.quotations.accepted';
    }

    protected function viewData(): array
    {
        return [
            'quotation' => $this->quotation,
            'forAdmin' => $this->forAdmin,
            'url' => $this->url(),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->quotation->reference,
            'client_name' => $this->quotation->client->company_name,
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return $this->forAdmin
            ? route('admin.quotations.show', $this->quotation)
            : route('quotation.canonical', $this->quotation->slug);
    }
}
