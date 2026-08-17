<?php

namespace App\Actions\Quotations;

use App\Enums\QuotationStatus;
use App\Mail\QuotationExpiredMail;
use App\Mail\QuotationReminderMail;
use App\Models\ActivityLog;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Runs daily (see routes/console.php): flips overdue quotations to Expired
 * and fires the 3-day / 1-day / day-of reminder emails (Section 11).
 */
class ExpireQuotationsAction
{
    public function handle(): void
    {
        $this->sendReminders();
        $this->expireOverdue();
    }

    private function sendReminders(): void
    {
        $pending = Quotation::query()
            ->whereIn('status', [QuotationStatus::Sent, QuotationStatus::Viewed])
            ->whereNotNull('expiry_date')
            ->get();

        foreach ($pending as $quotation) {
            $daysRemaining = (int) now()->startOfDay()->diffInDays($quotation->expiry_date, false);

            if ($daysRemaining === 3 && ! $quotation->reminder_3d_sent_at) {
                Mail::to($quotation->client->email)->queue(new QuotationReminderMail($quotation, 3));
                $quotation->forceFill(['reminder_3d_sent_at' => now()])->save();
            } elseif ($daysRemaining === 1 && ! $quotation->reminder_1d_sent_at) {
                Mail::to($quotation->client->email)->queue(new QuotationReminderMail($quotation, 1));
                $quotation->forceFill(['reminder_1d_sent_at' => now()])->save();
            }
        }
    }

    private function expireOverdue(): void
    {
        $overdue = Quotation::query()
            ->whereIn('status', [QuotationStatus::Sent, QuotationStatus::Viewed])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString())
            ->get();

        foreach ($overdue as $quotation) {
            $quotation->forceFill(['status' => QuotationStatus::Expired])->save();

            ActivityLog::record(
                action: 'quotation.expired',
                description: "Quotation {$quotation->reference} expired without a decision.",
                subject: $quotation,
                client: $quotation->client,
            );

            Mail::to($quotation->client->email)->queue(new QuotationExpiredMail($quotation, forAdmin: false));
            User::admins()->get()->each(
                fn (User $admin) => Mail::to($admin->email)->queue(new QuotationExpiredMail($quotation, forAdmin: true))
            );
        }
    }
}
