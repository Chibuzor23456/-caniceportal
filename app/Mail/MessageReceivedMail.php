<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MessageReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Message $message,
        public bool $forAdmin = false,
    ) {}

    public function build(): self
    {
        return $this->subject($this->forAdmin
                ? "New message from {$this->message->client->company_name}"
                : 'New message from Canice Technologies')
            ->markdown('emails.messages.received', [
                'message' => $this->message,
                'forAdmin' => $this->forAdmin,
                'url' => $this->forAdmin
                    ? route('admin.messages.show', $this->message->client)
                    : route('client.messages.index'),
            ]);
    }
}
