<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoicePaidMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function build(): self
    {
        return $this->subject("Payment received - {$this->invoice->reference}")
            ->markdown('emails.invoices.paid', [
                'invoice' => $this->invoice,
                'url' => route('client.invoices.show', $this->invoice),
            ]);
    }
}
