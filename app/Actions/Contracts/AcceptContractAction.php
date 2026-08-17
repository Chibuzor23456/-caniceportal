<?php

namespace App\Actions\Contracts;

use App\Enums\ContractStatus;
use App\Enums\SignatureType;
use App\Mail\ContractAcceptedMail;
use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\User;
use App\Notifications\GenericNotification;
use App\Services\ContractPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AcceptContractAction
{
    public function __construct(private ContractPdfService $pdf) {}

    /**
     * Returns true if this call is the one that actually recorded the
     * acceptance, false if a near-simultaneous request beat it to it - same
     * race-condition handling as AcceptQuotationAction (Section 10).
     */
    public function handle(Contract $contract, Request $request, string $signerName, SignatureType $signatureType, ?string $signatureImagePath = null): bool
    {
        $accepted = DB::transaction(function () use ($contract, $request, $signerName, $signatureType, $signatureImagePath) {
            /** @var Contract $locked */
            $locked = Contract::query()->lockForUpdate()->findOrFail($contract->id);

            if (! $locked->status->acceptsSignature()) {
                return false;
            }

            $locked->signatures()->create([
                'signer_name' => $signerName,
                'signature_type' => $signatureType,
                'signature_image_path' => $signatureImagePath,
                'signed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'device_summary' => $this->summarizeDevice((string) $request->userAgent()),
            ]);

            $locked->forceFill([
                'status' => ContractStatus::Accepted,
                'accepted_at' => now(),
                'secure_token_expires_at' => now()->addDays(30),
            ])->save();

            return true;
        });

        if (! $accepted) {
            return false;
        }

        $contract->refresh();

        if (! $contract->isUploaded()) {
            $this->pdf->generate($contract);
        }

        ActivityLog::record(
            action: 'contract.accepted',
            description: "Contract {$contract->reference} was accepted by {$contract->client->company_name}.",
            subject: $contract,
            client: $contract->client,
        );

        Mail::to($contract->client->email)->queue(new ContractAcceptedMail($contract, forAdmin: false));
        $contract->client->user?->notify(new GenericNotification(
            title: 'Contract accepted',
            body: "You accepted contract {$contract->reference}.",
            url: route('client.contracts.show', $contract),
        ));

        User::admins()->get()->each(function (User $admin) use ($contract) {
            Mail::to($admin->email)->queue(new ContractAcceptedMail($contract, forAdmin: true));
            $admin->notify(new GenericNotification(
                title: 'Contract accepted',
                body: "{$contract->client->company_name} accepted contract {$contract->reference}.",
                url: route('admin.contracts.show', $contract),
            ));
        });

        return true;
    }

    private function summarizeDevice(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Mobile') => 'Mobile device',
            str_contains($userAgent, 'Tablet') => 'Tablet',
            default => 'Desktop',
        };
    }
}
