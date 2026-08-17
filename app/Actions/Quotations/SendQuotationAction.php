<?php

namespace App\Actions\Quotations;

use App\Enums\QuotationStatus;
use App\Mail\QuotationSentMail;
use App\Models\ActivityLog;
use App\Models\Quotation;
use App\Notifications\GenericNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendQuotationAction
{
    public function handle(Quotation $quotation): Quotation
    {
        $quotation->forceFill([
            'status' => QuotationStatus::Sent,
            'sent_at' => now(),
            'issue_date' => $quotation->issue_date ?? now()->toDateString(),
            'expiry_date' => now()->addDays(14)->toDateString(),
            'secure_token' => Str::random(48),
            'secure_token_expires_at' => now()->addDays(14),
        ])->save();

        ActivityLog::record(
            action: 'quotation.sent',
            description: "Quotation {$quotation->reference} was sent to {$quotation->client->company_name}.",
            subject: $quotation,
            client: $quotation->client,
        );

        Mail::to($quotation->client->email)->queue(new QuotationSentMail($quotation));

        $quotation->client->user?->notify(new GenericNotification(
            title: 'New quotation',
            body: "Quotation {$quotation->reference} is ready for your review.",
            url: route('client.quotations.show', $quotation),
            type: 'quotation',
        ));

        return $quotation;
    }
}
