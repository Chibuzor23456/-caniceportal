<?php

namespace App\Mail;

use App\Models\Client;

class ClientWelcomeMail extends TemplatedMail
{
    public function __construct(
        public Client $client,
        public string $temporaryPassword,
    ) {}

    protected function type(): string
    {
        return 'client_welcome';
    }

    protected function fallbackSubject(): string
    {
        return 'Welcome to the Canice Technologies Client Portal';
    }

    protected function mailView(): string
    {
        return 'emails.clients.welcome';
    }

    protected function viewData(): array
    {
        return [
            'client' => $this->client,
            'temporaryPassword' => $this->temporaryPassword,
            'loginUrl' => route('login'),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'client_name' => $this->client->contact_person,
            'login_url' => route('login'),
            'temporary_password' => $this->temporaryPassword,
        ];
    }
}
