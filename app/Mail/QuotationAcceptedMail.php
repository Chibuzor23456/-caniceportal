<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationAcceptedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public bool $forAdmin = false,
    ) {}

    public function build(): self
    {
        return $this->subject("Quotation {$this->quotation->reference} was accepted")
            ->markdown('emails.quotations.accepted', [
                'quotation' => $this->quotation,
                'forAdmin' => $this->forAdmin,
                'url' => $this->forAdmin
                    ? route('admin.quotations.show', $this->quotation)
                    : route('quotation.canonical', $this->quotation->slug),
            ]);
    }
}
