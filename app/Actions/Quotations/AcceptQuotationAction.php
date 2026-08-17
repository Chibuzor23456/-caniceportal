<?php

namespace App\Actions\Quotations;

use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Enums\SignatureType;
use App\Mail\QuotationAcceptedMail;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Notifications\GenericNotification;
use App\Services\QuotationPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AcceptQuotationAction
{
    public function __construct(private QuotationPdfService $pdf) {}

    /**
     * Returns true if this call is the one that actually recorded the
     * acceptance, false if the quotation was already accepted by a
     * near-simultaneous request (Section 10's race-condition handling).
     */
    public function handle(Quotation $quotation, Request $request, string $signerName, SignatureType $signatureType, ?string $signatureImagePath = null): bool
    {
        $accepted = DB::transaction(function () use ($quotation, $request, $signerName, $signatureType, $signatureImagePath) {
            /** @var Quotation $locked */
            $locked = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);

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
                'status' => QuotationStatus::Accepted,
                'accepted_at' => now(),
                // Retire the token ~30 days after signature (Section 11) -
                // an old email link shouldn't stay a permanent door into a
                // signed legal document; the canonical /quotation/{slug}
                // page (session-gated) is the permanent fallback.
                'secure_token_expires_at' => now()->addDays(30),
            ])->save();

            return true;
        });

        if (! $accepted) {
            return false;
        }

        $quotation->refresh();

        $this->pdf->generate($quotation);

        Project::create([
            'client_id' => $quotation->client_id,
            'quotation_id' => $quotation->id,
            'title' => $quotation->client->company_name,
            'status' => ProjectStatus::Active,
        ]);

        ActivityLog::record(
            action: 'quotation.accepted',
            description: "Quotation {$quotation->reference} was accepted by {$quotation->client->company_name}.",
            subject: $quotation,
            client: $quotation->client,
        );

        Mail::to($quotation->client->email)->queue(new QuotationAcceptedMail($quotation, forAdmin: false));
        $quotation->client->user?->notify(new GenericNotification(
            title: 'Quotation accepted',
            body: "You accepted quotation {$quotation->reference}. A project has been created.",
            url: route('client.quotations.show', $quotation),
            type: 'quotation',
        ));

        User::admins()->get()->each(function (User $admin) use ($quotation) {
            Mail::to($admin->email)->queue(new QuotationAcceptedMail($quotation, forAdmin: true));
            $admin->notify(new GenericNotification(
                title: 'Quotation accepted',
                body: "{$quotation->client->company_name} accepted quotation {$quotation->reference}.",
                url: route('admin.quotations.show', $quotation),
                type: 'quotation',
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
