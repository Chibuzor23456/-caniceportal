<?php

use App\Actions\Email\PollBouncesAction;
use App\Actions\Invoices\OverdueInvoicesAction;
use App\Actions\Quotations\ExpireQuotationsAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cron-simulated queue (PRD Section 3): shared hosting can't run a persistent
// `queue:work` daemon, so a single "* * * * * php artisan schedule:run" cron
// entry (see README) drives this instead. Do not replace this with a daemon.
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute();

// Quotation validity (Section 10/11): expire overdue quotations and fire the
// 3-day / 1-day / day-of reminder emails.
Schedule::call(fn () => app(ExpireQuotationsAction::class)->handle())->daily();

// Invoice validity (Section 14): flip Sent invoices past their due date to Overdue.
Schedule::call(fn () => app(OverdueInvoicesAction::class)->handle())->daily();

// Bounce detection (Section 12): rides the same 1-minute cron already
// handling queues. No-ops until a real IMAP mailbox is configured.
Schedule::call(fn () => app(PollBouncesAction::class)->handle())->everyFiveMinutes();
