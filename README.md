# Canice Technologies Client Portal

A Laravel client management portal for Canice Technologies, replacing the previous
WhatsApp/email/spreadsheet workflow. See `canice-technologies-client-portal-PRD.md`
for the full product spec.

**Current status:** all 5 phases from the PRD's Build Order (Section 4) are built and
tested - Auth + Client Management, Quotations, Projects, Invoices, and
Messaging/Notifications/Activity Log/Search/Email Delivery Tracking. Deferred (not in
the Build Order): Testimonial capture, Contracts, File Management, the full Settings
module, and Section 8's "Extended Analytics" dashboard widgets. See
`canice-technologies-client-portal-PRD.md` Sections 4 and 17 for what's deliberately
out of scope for v1.

## Stack

- Laravel 13, PHP 8.3+, MySQL
- Livewire 4 + Alpine.js + Tailwind CSS 4 (Poppins, Canice Technologies brand palette)
- Laravel Fortify (auth, TOTP 2FA for admins)
- Cloudflare R2 (S3-compatible) for file storage
- Mail sent through PHPMailer over SMTP, wrapped behind Laravel's `Mail` facade (see
  `app/Mail/Transport/PHPMailerTransport.php`), not a third-party transactional API

## Brand assets

Logo variants live in `public/images/brand/` (`logo-full.png`, `logo-icon.png`,
`logo-full-dark-bg.png`, `logo-mono.png`), background-removed and cropped from the
originals supplied in the project root. The color palette and font are wired as
Tailwind theme tokens in `resources/css/app.css` (`--color-brand`, `--color-brand-emphasis`,
`--color-brand-accent`, `--color-navy`, `--color-canvas`) and `vite.config.js` (Poppins).
Any new primary action, active nav state, or accent should use `bg-brand` /
`text-brand` / `hover:bg-brand-emphasis`, never a new ad hoc color.

## Local Environment Notes (this machine)

PHP 8.4, Composer, and MySQL 8.4 were installed during this build session:
- PHP: `%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe`
  (added to PATH, but a new terminal may be needed for `php`/`composer` to resolve
  correctly the very first time)
- Composer: `%LOCALAPPDATA%\Composer\composer.bat`
- MySQL: `C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe`, data directory at
  `%USERPROFILE%\.mysql-data\canice-portal`. **It is not registered as a Windows
  service** (the sandboxed session didn't have the elevated rights to do that), so
  after a reboot, start it manually before running the app:
  ```powershell
  & "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe" --datadir="$env:USERPROFILE\.mysql-data\canice-portal" --basedir="C:\Program Files\MySQL\MySQL Server 8.4" --port=3306
  ```
  Local DB credentials are in `.env` (`canice_dev` / see `DB_PASSWORD`); root password
  is `RootDevPass_2026!`. If you'd rather have MySQL start automatically, run
  `mysqld --install` from an elevated (Run as Administrator) terminal.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
- `DB_*`: point at a local MySQL database.
- `MAIL_MAILER=log` is fine for local dev (mail is written to `storage/logs/laravel.log`
  instead of actually sending). Switch to `smtp` with real Hostinger credentials before
  any real email needs to go out; sending itself goes through PHPMailer either way.
- `R2_*`: only needed once a file-upload feature (Phase 2+) is built, safe to leave
  blank for now.

```bash
php artisan migrate --seed
npm run build   # or `npm run dev` while working on views
php artisan serve
```

The seeder creates the one admin account and prints its email/temporary password to
the console **once**, it isn't stored anywhere else, so save it immediately. The admin
is forced through TOTP two-factor setup on first login (Section 7 of the PRD).

Clients are never created by seeding, they're onboarded through the admin UI
(Clients -> New Client), which generates their login credentials, emails them,
and logs the event automatically.

## Deployment (Hostinger)

This app is designed to deploy on Hostinger shared hosting, per Section 3 of the PRD.
A few things are load-bearing there and shouldn't be "fixed" without re-reading that
section first:

- **Queue processing is cron-simulated, not a persistent worker.** Shared hosting kills
  long-running processes, so don't run `php artisan queue:work` as a daemon. Instead,
  add a single cron entry that fires every minute:

  ```
  * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
  ```

  The actual queue-draining call is already registered in `routes/console.php` via
  `Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute();`.
  This means background jobs (queued emails, and PDF generation/reminders in later
  phases) run with up to about a minute of delay, expected, not a bug.

- **Mail is sent through PHPMailer**, wrapped behind Laravel's `Mail` facade via a
  custom transport (`app/Mail/Transport/PHPMailerTransport.php`, registered in
  `AppServiceProvider`). Application code (Mailables, `Mail::to(...)->queue(...)`)
  never touches PHPMailer directly, only this transport does, so it can be swapped
  later without touching call sites. Set `MAIL_MAILER=smtp` with real Hostinger SMTP
  credentials in production.

- **All client-facing files live on Cloudflare R2** (the `r2` disk in
  `config/filesystems.php`), never local disk, set the `R2_*` env vars before any
  upload feature ships. Links to files on this disk must always be generated as
  signed/time-limited URLs, never public.

- **Bounce detection polls a dedicated IMAP mailbox** (`app/Actions/Email/PollBouncesAction.php`,
  scheduled every 5 minutes in `routes/console.php`). It no-ops until `IMAP_HOST` (and
  `IMAP_PORT`/`IMAP_USERNAME`/`IMAP_PASSWORD`) are set - create the mailbox on Hostinger
  first, then fill those in.

## Tests

```bash
php artisan test
```
