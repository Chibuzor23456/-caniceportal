<?php

namespace App\Actions\Contracts;

use App\Enums\ContractStatus;
use App\Mail\ContractSentMail;
use App\Models\ActivityLog;
use App\Models\Contract;
use App\Notifications\GenericNotification;
use App\Services\ContractPdfService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendContractAction
{
    public function __construct(private ContractPdfService $pdf) {}

    public function handle(Contract $contract): Contract
    {
        $contract->forceFill([
            'status' => ContractStatus::Sent,
            'sent_at' => now(),
            'issue_date' => $contract->issue_date ?? now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'secure_token' => Str::random(48),
            'secure_token_expires_at' => now()->addDays(30),
        ])->save();

        if (! $contract->isUploaded()) {
            $this->pdf->generate($contract);
        }

        ActivityLog::record(
            action: 'contract.sent',
            description: "Contract {$contract->reference} was sent to {$contract->client->company_name}.",
            subject: $contract,
            client: $contract->client,
        );

        Mail::to($contract->client->email)->queue(new ContractSentMail($contract));

        $contract->client->user?->notify(new GenericNotification(
            title: 'New contract',
            body: "Contract {$contract->reference} is ready for your review.",
            url: route('client.contracts.show', $contract),
        ));

        return $contract;
    }
}
