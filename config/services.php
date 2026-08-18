<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Section 12's monitored bounce mailbox. Unset in dev - PollBouncesAction
    // no-ops until a real Hostinger mailbox exists.
    'imap' => [
        'host' => env('IMAP_HOST'),
        'port' => env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'username' => env('IMAP_USERNAME'),
        'password' => env('IMAP_PASSWORD'),
    ],

    // GitHub deploy webhook (see README "Auto-Deploy"). `secret` empty in dev,
    // which DeployWebhookController treats as "reject every request" rather
    // than silently trusting an unconfigured secret. `github_token` is a
    // fine-grained PAT (Contents: Read-only, scoped to just this repo) used
    // only to download the built `production` branch as a zip - a different
    // credential from GitHub Actions' own auto-issued token that pushes to
    // that branch in the first place.
    'deploy_webhook' => [
        'secret' => env('DEPLOY_WEBHOOK_SECRET'),
        'github_token' => env('DEPLOY_GITHUB_TOKEN'),
        'repo' => env('DEPLOY_REPO', 'Chibuzor23456/-caniceportal'),
        'branch' => env('DEPLOY_BRANCH', 'production'),
        // Absolute path to the web server's actual document root, only
        // needed when it's a fixed sibling directory the app can't be
        // installed into directly (e.g. Hostinger's public_html) - see
        // README "Auto-Deploy". Null skips the second sync entirely.
        'public_path' => env('DEPLOY_PUBLIC_PATH'),
    ],

];
