<?php

namespace Tests\Feature;

use App\Jobs\DeployApplication;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use Tests\TestCase;

class DeployApplicationTest extends TestCase
{
    private string $sourceRoot;

    private string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceRoot = sys_get_temp_dir().'/deploy-source-'.uniqid();
        $this->publicPath = sys_get_temp_dir().'/deploy-public-'.uniqid();

        File::ensureDirectoryExists($this->sourceRoot.'/public/build');
        File::put($this->sourceRoot.'/public/index.php', '<?php // entry point');
        File::put($this->sourceRoot.'/public/build/app.abc123.js', 'console.log(1);');
        File::ensureDirectoryExists($this->publicPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->sourceRoot);
        File::deleteDirectory($this->publicPath);

        parent::tearDown();
    }

    private function callSyncPublicPath(): void
    {
        $job = new DeployApplication;
        $method = new ReflectionMethod($job, 'syncPublicPath');
        $method->invoke($job, $this->sourceRoot, Log::channel('deploy'));
    }

    public function test_public_folder_syncs_onto_the_configured_document_root_when_set(): void
    {
        config(['services.deploy_webhook.public_path' => $this->publicPath]);

        $this->callSyncPublicPath();

        $this->assertFileExists($this->publicPath.'/index.php');
        $this->assertFileExists($this->publicPath.'/build/app.abc123.js');
        $this->assertSame('console.log(1);', File::get($this->publicPath.'/build/app.abc123.js'));
    }

    public function test_stale_build_assets_removed_upstream_are_deleted_from_the_document_root(): void
    {
        config(['services.deploy_webhook.public_path' => $this->publicPath]);
        File::ensureDirectoryExists($this->publicPath.'/build');
        File::put($this->publicPath.'/build/old-app.xyz999.js', 'stale');

        $this->callSyncPublicPath();

        $this->assertFileDoesNotExist($this->publicPath.'/build/old-app.xyz999.js');
        $this->assertFileExists($this->publicPath.'/build/app.abc123.js');
    }

    public function test_the_sync_is_skipped_entirely_when_no_public_path_is_configured(): void
    {
        config(['services.deploy_webhook.public_path' => null]);

        $this->callSyncPublicPath();

        $this->assertFileDoesNotExist($this->publicPath.'/index.php');
    }
}
