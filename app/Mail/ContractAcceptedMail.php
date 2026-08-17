<?php

namespace App\Mail;

use App\Models\Contract;

class ContractAcceptedMail extends TemplatedMail
{
    public function __construct(
        public Contract $contract,
        public bool $forAdmin = false,
    ) {}

    protected function type(): string
    {
        return 'contract_accepted';
    }

    protected function fallbackSubject(): string
    {
        return "Contract {$this->contract->reference} was accepted";
    }

    protected function mailView(): string
    {
        return 'emails.contracts.accepted';
    }

    protected function viewData(): array
    {
        return [
            'contract' => $this->contract,
            'forAdmin' => $this->forAdmin,
            'url' => $this->url(),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->contract->reference,
            'client_name' => $this->contract->client->company_name,
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return $this->forAdmin
            ? route('admin.contracts.show', $this->contract)
            : route('contract.canonical', $this->contract->slug);
    }
}
