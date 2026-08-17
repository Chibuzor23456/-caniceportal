<?php

namespace Tests\Feature;

use App\Support\DirectorySync;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DirectorySyncTest extends TestCase
{
    private string $sourceDir;

    private string $targetDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceDir = sys_get_temp_dir().'/dirsync-source-'.uniqid();
        $this->targetDir = sys_get_temp_dir().'/dirsync-target-'.uniqid();

        File::ensureDirectoryExists($this->sourceDir);
        File::ensureDirectoryExists($this->targetDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->sourceDir);
        File::deleteDirectory($this->targetDir);

        parent::tearDown();
    }

    private function write(string $root, string $relativePath, string $content): void
    {
        $path = $root.DIRECTORY_SEPARATOR.$relativePath;
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }

    public function test_new_files_are_added(): void
    {
        $this->write($this->sourceDir, 'app/routes.php', 'new content');

        (new DirectorySync)->sync($this->sourceDir, $this->targetDir);

        $this->assertSame('new content', File::get($this->targetDir.'/app/routes.php'));
    }

    public function test_changed_files_are_overwritten(): void
    {
        $this->write($this->sourceDir, 'config.php', 'v2');
        $this->write($this->targetDir, 'config.php', 'v1');

        (new DirectorySync)->sync($this->sourceDir, $this->targetDir);

        $this->assertSame('v2', File::get($this->targetDir.'/config.php'));
    }

    public function test_files_removed_from_source_are_deleted_from_target(): void
    {
        $this->write($this->sourceDir, 'keep.php', 'kept');
        $this->write($this->targetDir, 'keep.php', 'kept');
        $this->write($this->targetDir, 'stale/old-controller.php', 'delete me');

        $result = (new DirectorySync)->sync($this->sourceDir, $this->targetDir);

        $this->assertFileDoesNotExist($this->targetDir.'/stale/old-controller.php');
        $this->assertFileExists($this->targetDir.'/keep.php');
        $this->assertSame(1, $result['synced']);
        $this->assertSame(1, $result['deleted']);
    }

    public function test_excluded_paths_are_never_deleted_even_when_absent_from_source(): void
    {
        $this->write($this->sourceDir, 'app/routes.php', 'source');
        $this->write($this->targetDir, '.env', 'SECRET=do-not-touch');
        $this->write($this->targetDir, 'storage/logs/laravel.log', 'log lines');

        (new DirectorySync)->sync($this->sourceDir, $this->targetDir, ['.env', 'storage']);

        $this->assertSame('SECRET=do-not-touch', File::get($this->targetDir.'/.env'));
        $this->assertSame('log lines', File::get($this->targetDir.'/storage/logs/laravel.log'));
    }

    public function test_excluded_paths_are_never_overwritten_even_when_present_in_source(): void
    {
        // A malformed or malicious build artifact containing a .env should
        // never be able to clobber the live one - belt and suspenders on
        // top of GitHub Actions never producing one in the first place.
        $this->write($this->sourceDir, '.env', 'SOMETHING_FROM_THE_BUILD');
        $this->write($this->targetDir, '.env', 'REAL_PRODUCTION_SECRET');

        (new DirectorySync)->sync($this->sourceDir, $this->targetDir, ['.env']);

        $this->assertSame('REAL_PRODUCTION_SECRET', File::get($this->targetDir.'/.env'));
    }

    public function test_empty_directories_left_behind_by_deletions_are_pruned(): void
    {
        $this->write($this->sourceDir, 'keep.php', 'kept');
        $this->write($this->targetDir, 'keep.php', 'kept');
        $this->write($this->targetDir, 'stale-dir/only-file.php', 'delete me');

        (new DirectorySync)->sync($this->sourceDir, $this->targetDir);

        $this->assertDirectoryDoesNotExist($this->targetDir.'/stale-dir');
    }
}
