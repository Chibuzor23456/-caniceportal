<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Contract $contract) {}

    public function build(): self
    {
        return $this->subject("Contract {$this->contract->reference} was declined")
            ->markdown('emails.contracts.rejected', [
                'contract' => $this->contract,
                'url' => route('admin.contracts.show', $this->contract),
            ]);
    }
}
