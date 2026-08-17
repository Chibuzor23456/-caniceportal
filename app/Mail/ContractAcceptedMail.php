<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractAcceptedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public bool $forAdmin = false,
    ) {}

    public function build(): self
    {
        return $this->subject("Contract {$this->contract->reference} was accepted")
            ->markdown('emails.contracts.accepted', [
                'contract' => $this->contract,
                'forAdmin' => $this->forAdmin,
                'url' => $this->forAdmin
                    ? route('admin.contracts.show', $this->contract)
                    : route('contract.canonical', $this->contract->slug),
            ]);
    }
}
