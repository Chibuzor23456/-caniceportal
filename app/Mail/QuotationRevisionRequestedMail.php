<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationRevisionRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation) {}

    public function build(): self
    {
        return $this->subject("Revision requested for expired quotation {$this->quotation->reference}")
            ->markdown('emails.quotations.revision-requested', [
                'quotation' => $this->quotation,
                'adminUrl' => route('admin.quotations.show', $this->quotation),
            ]);
    }
}
