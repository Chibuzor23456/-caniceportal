<?php

namespace App\Mail;

use App\Models\Contract;

class ContractViewedAdminMail extends TemplatedMail
{
    public function __construct(public Contract $contract) {}

    protected function type(): string
    {
        return 'contract_viewed_admin';
    }

    protected function fallbackSubject(): string
    {
        return "Contract {$this->contract->reference} was viewed";
    }

    protected function mailView(): string
    {
        return 'emails.contracts.viewed-admin';
    }

    protected function viewData(): array
    {
        return [
            'contract' => $this->contract,
            'url' => route('admin.contracts.show', $this->contract),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->contract->reference,
            'client_name' => $this->contract->client->company_name,
            'url' => route('admin.contracts.show', $this->contract),
        ];
    }
}
