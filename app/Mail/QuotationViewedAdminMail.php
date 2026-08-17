<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationViewedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation) {}

    public function build(): self
    {
        return $this->subject("Quotation {$this->quotation->reference} was just viewed")
            ->markdown('emails.quotations.viewed-admin', [
                'quotation' => $this->quotation,
                'adminUrl' => route('admin.quotations.show', $this->quotation),
            ]);
    }
}
