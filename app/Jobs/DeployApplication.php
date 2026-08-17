<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Runs the actual pull/install/build/migrate sequence (see README
 * "Auto-Deploy"). Dispatched by DeployWebhookController rather than run
 * inline, since GitHub expects a fast response and this easily exceeds
 * that. Executes on the existing cron-driven queue drain
 * (routes/console.php), no new infrastructure.
 */
class DeployApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $timeout = 600;

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

        $steps = [
            ['git', 'pull', 'origin', 'main'],
            ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'],
            ['npm', 'ci'],
            ['npm', 'run', 'build'],
            ['php', 'artisan', 'migrate', '--force'],
            ['php', 'artisan', 'view:clear'],
            ['php', 'artisan', 'route:clear'],
            ['php', 'artisan', 'config:clear'],
        ];

        foreach ($steps as $command) {
            $process = new Process($command, base_path(), null, null, 300);
            $process->run();

            $log->info('$ '.implode(' ', $command));
            $log->info($process->getOutput());

            if (! $process->isSuccessful()) {
                $log->error("Step failed (exit {$process->getExitCode()}): ".$process->getErrorOutput());
                $this->notifyAdmins(success: false, failedStep: implode(' ', $command));

                return;
            }
        }

        $log->info('Deploy finished successfully.');
        $this->notifyAdmins(success: true);
    }

    private function notifyAdmins(bool $success, ?string $failedStep = null): void
    {
        $title = $success ? 'Deploy succeeded' : 'Deploy failed';
        $body = $success
            ? 'The latest push to main is now live.'
            : "Deploy failed at: {$failedStep}. See storage/logs/deploy.log for details.";

        User::admins()->get()->each(
            fn (User $admin) => $admin->notify(new GenericNotification(title: $title, body: $body))
        );
    }
}
