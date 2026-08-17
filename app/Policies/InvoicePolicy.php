<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isClient() && $invoice->client_id === $user->client?->id;
    }

    public function uploadProof(User $user, Invoice $invoice): bool
    {
        return $user->isClient()
            && $invoice->client_id === $user->client?->id
            && in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue], true);
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin()
            && in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue], true);
    }
}
