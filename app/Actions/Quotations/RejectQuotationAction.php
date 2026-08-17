<?php

namespace App\Actions\Quotations;

use App\Enums\QuotationStatus;
use App\Mail\QuotationRejectedMail;
use App\Models\ActivityLog;
use App\Models\Quotation;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RejectQuotationAction
{
    public function handle(Quotation $quotation, string $reason): bool
    {
        $rejected = DB::transaction(function () use ($quotation, $reason) {
            /** @var Quotation $locked */
            $locked = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);

            if (! $locked->status->acceptsSignature()) {
                return false;
            }

            $locked->forceFill([
                'status' => QuotationStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            return true;
        });

        if (! $rejected) {
            return false;
        }

        $quotation->refresh();

        ActivityLog::record(
            action: 'quotation.rejected',
            description: "Quotation {$quotation->reference} was declined by {$quotation->client->company_name}.",
            subject: $quotation,
            client: $quotation->client,
        );

        User::admins()->get()->each(function (User $admin) use ($quotation) {
            Mail::to($admin->email)->queue(new QuotationRejectedMail($quotation));
            $admin->notify(new GenericNotification(
                title: 'Quotation declined',
                body: "{$quotation->client->company_name} declined quotation {$quotation->reference}.",
                url: route('admin.quotations.show', $quotation),
                type: 'quotation',
            ));
        });

        return true;
    }
}
