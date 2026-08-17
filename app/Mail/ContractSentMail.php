<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Contract $contract) {}

    public function build(): self
    {
        return $this->subject("New Contract from Canice Technologies ({$this->contract->reference})")
            ->markdown('emails.contracts.sent', [
                'contract' => $this->contract,
                'secureUrl' => route('contract.secure', $this->contract->secure_token),
            ]);
    }
}
