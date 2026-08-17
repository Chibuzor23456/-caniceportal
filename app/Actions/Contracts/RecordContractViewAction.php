<?php

namespace App\Actions\Contracts;

use App\Enums\ContractStatus;
use App\Mail\ContractViewedAdminMail;
use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\Mail;

class RecordContractViewAction
{
    public function handle(Contract $contract): void
    {
        if ($contract->viewed_at || $contract->status !== ContractStatus::Sent) {
            return;
        }

        $contract->forceFill([
            'status' => ContractStatus::Viewed,
            'viewed_at' => now(),
        ])->save();

        ActivityLog::record(
            action: 'contract.viewed',
            description: "Contract {$contract->reference} was viewed by {$contract->client->company_name}.",
            subject: $contract,
            client: $contract->client,
        );

        User::admins()->get()->each(function (User $admin) use ($contract) {
            Mail::to($admin->email)->queue(new ContractViewedAdminMail($contract));
            $admin->notify(new GenericNotification(
                title: 'Contract viewed',
                body: "{$contract->client->company_name} viewed contract {$contract->reference}.",
                url: route('admin.contracts.show', $contract),
                type: 'contract',
            ));
        });
    }
}
