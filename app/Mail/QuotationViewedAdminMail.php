<?php

namespace App\Mail;

use App\Models\Quotation;

class QuotationViewedAdminMail extends TemplatedMail
{
    public function __construct(public Quotation $quotation) {}

    protected function type(): string
    {
        return 'quotation_viewed_admin';
    }

    protected function fallbackSubject(): string
    {
        return "Quotation {$this->quotation->reference} was just viewed";
    }

    protected function mailView(): string
    {
        return 'emails.quotations.viewed-admin';
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
