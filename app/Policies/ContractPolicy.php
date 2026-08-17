<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Contract $contract): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isClient() && $contract->client_id === $user->client?->id;
    }
}
