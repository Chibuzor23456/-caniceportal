# Canice Technologies Client Portal

A Laravel client management portal for Canice Technologies, replacing the previous
WhatsApp/email/spreadsheet workflow. See `canice-technologies-client-portal-PRD.md`
for the full product spec.

**Current status:** every area named in the PRD is built and tested - the 5 phases
from the Build Order (Section 4: Auth + Client Management, Quotations, Projects,
Invoices, Messaging/Notifications/Activity Log/Search/Email Delivery Tracking) plus
everything deferred out of that order (Contracts, File Management, Testimonial
capture, the full Settings module including Email Templates, and Section 8's
Extended Analytics). See `canice-technologies-client-portal-PRD.md` Section 17 for
what's deliberately still out of scope for v1 (calendar sync, team/support-ticket
features).

## Stack

- Laravel 13, PHP 8.3+, MySQL
- Livewire 4 + Alpine.js + Tailwind CSS 4 (Poppins, Canice Technologies brand palette)
- Laravel Fortify (auth)
- Local disk storage (`storage/app/private`), served through Laravel's built-in
  signed-URL route - no third-party storage account
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
- File uploads work out of the box with no extra setup - everything is stored on
  the local disk.

```bash
php artisan migrate --seed
npm run build   # or `npm run dev` while working on views
php artisan serve
```

The seeder creates the one admin account and prints its email/temporary password to
the console **once**, it isn't stored anywhere else, so save it immediately.

Clients are never created by seeding, they're onboarded through the admin UI
(Clients -> New Client), which generates their login credentials, emails them,
and logs the event automatically.

## Deployment (Hostinger)

This app is designed to deploy on Hostinger shared hosting, per Section 3 of the PRD,
at **`https://portal.okwudilicanice.com`**. A few things are load-bearing there and
shouldn't be "fixed" without re-reading that section first:

- **First-run setup is a web wizard, not SSH commands.** After uploading the code,
  copy `.env.example` to `.env` and set `APP_URL=https://portal.okwudilicanice.com`
  and `APP_ENV=production` (leave `DB_*`/`MAIL_*` blank - the wizard fills those in),
  then visit `/install`. It walks through Database -> Email (SMTP, with a Test
  Connection button) -> Admin Account, writing straight to `.env` and running
  migrations for you. It generates `APP_KEY` itself if `.env` doesn't have one yet, so
  no `php artisan key:generate` step is required either. **Complete it immediately
  after upload, before sharing the URL with anyone** - it locks itself permanently
  (`storage/app/installed.lock`, plus a check for an existing admin account either
  way) the moment the admin account is created, but until then it's reachable by
  anyone who finds it. See `app/Http/Middleware/EnsureNotInstalled.php` for exactly
  what "installed" means before touching this.

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

- **All client-facing files live on the `local` disk** (`storage/app/private`, `serve
  => true` in `config/filesystems.php`), which registers Laravel's own signed-URL
  serving route (`storage.local`) automatically. Links to files on this disk must
  always be generated via `URL::temporarySignedRoute('storage.local', $expiration,
  ['path' => $path])`, never a plain `Storage::url()` - that route rejects any
  request without a valid signature.

- **Bounce detection polls a dedicated IMAP mailbox** (`app/Actions/Email/PollBouncesAction.php`,
  scheduled every 5 minutes in `routes/console.php`). It no-ops until `IMAP_HOST` (and
  `IMAP_PORT`/`IMAP_USERNAME`/`IMAP_PASSWORD`) are set - create the mailbox on Hostinger
  first, then fill those in.

## Deploying an update (manual)

There is no auto-deploy pipeline - every update is a manual build-and-upload via
Hostinger's File Manager, no SSH, no GitHub webhook, no CI. This was a deliberate
choice (an earlier GitHub Actions + webhook pipeline was tried and removed).

**Locally, before every deploy:**

```bash
composer install --no-dev --optimize-autoloader
npm run build
```

This produces `vendor/` (normally gitignored) and `public/build/` (Vite's hashed
assets) - both need to be part of what you upload, even though neither lives in git.

**Document root on Hostinger.** Shared hosting fixes the domain's document root at
`public_html` with no way to point it at a subfolder - but Laravel's document root
has to be its own `public/` folder, never the project root, or every other file
(`app/`, `config/`, `database/`, `.env`, ...) ends up sitting in the publicly-served
directory. **Never extract this repo directly into `public_html`.** The correct
layout, kept in place across every deploy:

- `caniceportal_app/` (a sibling directory next to `public_html`, same level, not
  inside it) holds everything *except* the contents of `public/`: `app/`,
  `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/`,
  `vendor/`, `.env`, `artisan`, etc.
- `public_html/` holds the *contents* of `public/` directly (`index.php`,
  `.htaccess`, `build/`, `favicon.ico`, ...) - not a `public_html/public/`
  subfolder.
- `public_html/index.php`'s three `__DIR__.'/../...'` paths (maintenance check,
  `vendor/autoload.php`, `bootstrap/app.php`) must point at
  `__DIR__.'/../caniceportal_app/...'`, since it no longer sits next to those
  folders the way a stock Laravel `public/index.php` expects.

**Every deploy after the first one:**

1. Upload the new/changed files into `caniceportal_app` (everything except
   `public/`) via File Manager, overwriting what's there. **Never overwrite `.env`
   or `storage/`** - those hold live config and uploaded files, not build output.
2. Upload `public/`'s contents into `public_html`, overwriting what's there
   (this is what actually ships new CSS/JS/index.php changes - easy to forget).
3. If the update includes new migrations, they need to run somehow. There's no
   SSH terminal in this workflow, so use hPanel's **Advanced -> Cron Jobs** to add
   a one-off (or `@reboot`-style single-run) job: `php /path/to/caniceportal_app/artisan migrate --force`.
   Remove the cron entry again after it's run once - it doesn't need to stay.

Existing background jobs (queued emails, etc.) keep running regardless via the cron
entry described below - only new *migrations* need this extra manual step.

## Tests

```bash
php artisan test
```
