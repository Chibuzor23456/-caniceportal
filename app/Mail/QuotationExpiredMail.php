<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public bool $forAdmin = false,
    ) {}

    public function build(): self
    {
        return $this->subject("Quotation {$this->quotation->reference} has expired")
            ->markdown('emails.quotations.expired', [
                'quotation' => $this->quotation,
                'forAdmin' => $this->forAdmin,
            ]);
    }
}
