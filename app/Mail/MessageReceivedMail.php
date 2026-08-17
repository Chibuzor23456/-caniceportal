<?php

namespace App\Mail;

use App\Models\Message;

class MessageReceivedMail extends TemplatedMail
{
    public function __construct(
        public Message $message,
        public bool $forAdmin = false,
    ) {}

    protected function type(): string
    {
        return $this->forAdmin ? 'message_received_admin' : 'message_received_client';
    }

    protected function fallbackSubject(): string
    {
        return $this->forAdmin
            ? "New message from {$this->message->client->company_name}"
            : 'New message from Canice Technologies';
    }

    protected function mailView(): string
    {
        return 'emails.messages.received';
    }

    protected function viewData(): array
    {
        return [
            'message' => $this->message,
            'forAdmin' => $this->forAdmin,
            'url' => $this->url(),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'client_name' => $this->message->client->company_name,
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return $this->forAdmin
            ? route('admin.messages.show', $this->message->client)
            : route('client.messages.index');
    }
}
