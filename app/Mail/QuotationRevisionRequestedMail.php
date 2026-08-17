<?php

namespace App\Mail;

use App\Models\Quotation;

class QuotationRevisionRequestedMail extends TemplatedMail
{
    public function __construct(public Quotation $quotation) {}

    protected function type(): string
    {
        return 'quotation_revision_requested';
    }

    protected function fallbackSubject(): string
    {
        return "Revision requested for expired quotation {$this->quotation->reference}";
    }

    protected function mailView(): string
    {
        return 'emails.quotations.revision-requested';
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
