<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\GenericNotification;
use App\Support\DirectorySync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Downloads the CI-built `production` branch as a zip and syncs it onto the
 * live app directory (see README "Auto-Deploy" and .github/workflows/deploy.yml
 * for the build side of this pipeline). No git/composer/npm needs to exist or
 * work on the server at all - everything here is a plain file operation.
 */
class DeployApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $timeout = 300;

    /**
     * Never deleted or overwritten by the sync, checked before every write.
     * Paths are relative to base_path().
     */
    private const EXCLUDED_PREFIXES = ['.env', 'storage', '.git'];

    public function handle(): void
    {
        $lockPath = storage_path('app/deploy.lock');
        $lockHandle = fopen($lockPath, 'c');

        if (! flock($lockHandle, LOCK_EX | LOCK_NB)) {
            Log::channel('deploy')->info('Deploy already in progress, skipping this run.');

            return;
        }

        try {
            $this->runDeploy();
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    private function runDeploy(): void
    {
        $log = Log::channel('deploy');
        $log->info('Deploy started.');

        $zipPath = storage_path('app/deploy-'.now()->timestamp.'.zip');
        $extractPath = storage_path('app/deploy-extract-'.now()->timestamp);

        try {
            if (! class_exists(ZipArchive::class)) {
                throw new RuntimeException('The zip PHP extension is not available on this server.');
            }

            $this->download($zipPath, $log);
            $this->extract($zipPath, $extractPath, $log);

            $sourceRoot = $this->findExtractedRoot($extractPath);

            $result = (new DirectorySync)->sync($sourceRoot, base_path(), self::EXCLUDED_PREFIXES);
            $log->info("{$result['synced']} file(s) synced, {$result['deleted']} file(s) deleted.");

            $this->runArtisanSteps($log);

            $log->info('Deploy finished successfully.');
            $this->notifyAdmins(success: true);
        } catch (Throwable $e) {
            $log->error('Deploy failed: '.$e->getMessage());
            $this->notifyAdmins(success: false, reason: $e->getMessage());
        } finally {
            File::delete($zipPath);
            File::deleteDirectory($extractPath);
        }
    }

    private function download(string $zipPath, $log): void
    {
        $repo = config('services.deploy_webhook.repo');
        $branch = config('services.deploy_webhook.branch');
        $token = config('services.deploy_webhook.github_token');

        $log->info("Downloading zipball for {$repo}@{$branch}.");

        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->timeout(120)
            ->get("https://api.github.com/repos/{$repo}/zipball/{$branch}");

        if (! $response->successful()) {
            throw new RuntimeException("Failed to download zipball: HTTP {$response->status()}.");
        }

        File::put($zipPath, $response->body());
    }

    private function extract(string $zipPath, string $extractPath, $log): void
    {
        $log->info('Extracting archive.');

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Failed to open the downloaded zip archive.');
        }

        $zip->extractTo($extractPath);
        $zip->close();
    }

    /**
     * GitHub's zipball wraps everything in a single {owner}-{repo}-{sha}/
     * folder - the actual deployable root is that one subdirectory.
     */
    private function findExtractedRoot(string $extractPath): string
    {
        $entries = array_values(array_diff(scandir($extractPath) ?: [], ['.', '..']));

        if (count($entries) !== 1 || ! is_dir($extractPath.DIRECTORY_SEPARATOR.$entries[0])) {
            throw new RuntimeException('Unexpected zip structure - expected a single top-level folder.');
        }

        return $extractPath.DIRECTORY_SEPARATOR.$entries[0];
    }

    private function runArtisanSteps($log): void
    {
        $steps = [
            ['migrate', ['--force' => true]],
            ['db:seed', ['--class' => \Database\Seeders\EmailTemplateSeeder::class, '--force' => true]],
            ['view:clear', []],
            ['route:clear', []],
            ['config:clear', []],
        ];

        foreach ($steps as [$command, $parameters]) {
            $log->info("Running: php artisan {$command}");
            Artisan::call($command, $parameters);
            $log->info(Artisan::output());
        }
    }

    private function notifyAdmins(bool $success, ?string $reason = null): void
    {
        $title = $success ? 'Deploy succeeded' : 'Deploy failed';
        $body = $success
            ? 'The latest push is now live.'
            : "Deploy failed: {$reason}. See storage/logs/deploy.log for details.";

        User::admins()->get()->each(
            fn (User $admin) => $admin->notify(new GenericNotification(title: $title, body: $body, type: 'system'))
        );
    }
}
