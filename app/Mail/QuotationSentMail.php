<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation) {}

    public function build(): self
    {
        return $this->subject("New Quotation from Canice Technologies ({$this->quotation->reference})")
            ->markdown('emails.quotations.sent', [
                'quotation' => $this->quotation,
                'secureUrl' => route('quotation.secure', $this->quotation->secure_token),
            ]);
    }
}
