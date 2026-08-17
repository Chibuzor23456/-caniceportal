<?php

namespace App\Mail;

use App\Models\Contract;

class ContractSentMail extends TemplatedMail
{
    public function __construct(public Contract $contract) {}

    protected function type(): string
    {
        return 'contract_sent';
    }

    protected function fallbackSubject(): string
    {
        return "New Contract from Canice Technologies ({$this->contract->reference})";
    }

    protected function mailView(): string
    {
        return 'emails.contracts.sent';
    }

    protected function viewData(): array
    {
        return [
            'contract' => $this->contract,
            'secureUrl' => route('contract.secure', $this->contract->secure_token),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'reference' => $this->contract->reference,
            'client_name' => $this->contract->client->company_name,
            'secure_url' => route('contract.secure', $this->contract->secure_token),
        ];
    }
}
