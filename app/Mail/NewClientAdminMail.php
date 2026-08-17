<?php

namespace App\Mail;

use App\Models\Client;

class NewClientAdminMail extends TemplatedMail
{
    public function __construct(public Client $client) {}

    protected function type(): string
    {
        return 'new_client_admin';
    }

    protected function fallbackSubject(): string
    {
        return "New client: {$this->client->company_name}";
    }

    protected function mailView(): string
    {
        return 'emails.clients.new-client-admin';
    }

    protected function viewData(): array
    {
        return [
            'client' => $this->client,
            'url' => route('admin.clients.show', $this->client),
        ];
    }

    protected function templateVariables(): array
    {
        return [
            'client_name' => $this->client->company_name,
            'url' => route('admin.clients.show', $this->client),
        ];
    }
}
