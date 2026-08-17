<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentProofUploadedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function build(): self
    {
        return $this->subject("Payment proof uploaded for {$this->invoice->reference}")
            ->markdown('emails.invoices.payment-proof-uploaded', [
                'invoice' => $this->invoice,
                'url' => route('admin.invoices.show', $this->invoice),
            ]);
    }
}
