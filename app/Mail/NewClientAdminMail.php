<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewClientAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Client $client) {}

    public function build(): self
    {
        return $this->subject("New client: {$this->client->company_name}")
            ->markdown('emails.clients.new-client-admin', [
                'client' => $this->client,
                'url' => route('admin.clients.show', $this->client),
            ]);
    }
}
