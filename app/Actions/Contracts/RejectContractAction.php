<?php

namespace App\Actions\Contracts;

use App\Enums\ContractStatus;
use App\Mail\ContractRejectedMail;
use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RejectContractAction
{
    public function handle(Contract $contract, string $reason): bool
    {
        $rejected = DB::transaction(function () use ($contract, $reason) {
            /** @var Contract $locked */
            $locked = Contract::query()->lockForUpdate()->findOrFail($contract->id);

            if (! $locked->status->acceptsSignature()) {
                return false;
            }

            $locked->forceFill([
                'status' => ContractStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return true;
        });

        if (! $rejected) {
            return false;
        }

        $contract->refresh();

        ActivityLog::record(
            action: 'contract.rejected',
            description: "Contract {$contract->reference} was declined by {$contract->client->company_name}.",
            subject: $contract,
            client: $contract->client,
        );

        User::admins()->get()->each(function (User $admin) use ($contract) {
            Mail::to($admin->email)->queue(new ContractRejectedMail($contract));
            $admin->notify(new GenericNotification(
                title: 'Contract declined',
                body: "{$contract->client->company_name} declined contract {$contract->reference}.",
                url: route('admin.contracts.show', $contract),
            ));
        });

        return true;
    }
}
