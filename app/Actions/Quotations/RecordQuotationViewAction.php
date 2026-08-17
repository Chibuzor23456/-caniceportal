<?php

namespace App\Actions\Quotations;

use App\Enums\QuotationStatus;
use App\Mail\QuotationViewedAdminMail;
use App\Models\ActivityLog;
use App\Models\Quotation;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\Mail;

class RecordQuotationViewAction
{
    public function handle(Quotation $quotation): void
    {
        // Only a Sent quotation transitions to Viewed. Anything already
        // Accepted/Rejected/Expired/Archived/Viewed is a terminal-ish state
        // that a later link-open must never clobber back to Viewed.
        if ($quotation->viewed_at || $quotation->status !== QuotationStatus::Sent) {
            return;
        }

        $quotation->forceFill([
            'status' => QuotationStatus::Viewed,
            'viewed_at' => now(),
        ])->save();

        ActivityLog::record(
            action: 'quotation.viewed',
            description: "Quotation {$quotation->reference} was viewed by {$quotation->client->company_name}.",
            subject: $quotation,
            client: $quotation->client,
        );

        User::admins()->get()->each(function (User $admin) use ($quotation) {
            Mail::to($admin->email)->queue(new QuotationViewedAdminMail($quotation));
            $admin->notify(new GenericNotification(
                title: 'Quotation viewed',
                body: "{$quotation->client->company_name} viewed quotation {$quotation->reference}.",
                url: route('admin.quotations.show', $quotation),
                type: 'quotation',
            ));
        });
    }
}
