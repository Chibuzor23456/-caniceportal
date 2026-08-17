<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public int $daysRemaining,
    ) {}

    public function build(): self
    {
        $subject = $this->daysRemaining <= 0
            ? "Quotation {$this->quotation->reference} expires today"
            : "Quotation {$this->quotation->reference} expires in {$this->daysRemaining} day".($this->daysRemaining === 1 ? '' : 's');

        return $this->subject($subject)
            ->markdown('emails.quotations.reminder', [
                'quotation' => $this->quotation,
                'daysRemaining' => $this->daysRemaining,
                'secureUrl' => route('quotation.secure', $this->quotation->secure_token),
            ]);
    }
}
