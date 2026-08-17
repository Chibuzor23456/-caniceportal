<?php

namespace App\Support;

use FilesystemIterator;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Syncs $source onto $target: every file in $source is copied/overwritten
 * into $target, then anything in $target that no longer exists in $source
 * gets deleted - a real sync, not a one-way copy, so files removed upstream
 * actually disappear. Paths under $excludedPrefixes (relative to the root)
 * are never touched in either direction, checked before every write.
 *
 * Used by App\Jobs\DeployApplication (Section: Auto-Deploy in README) to
 * apply a downloaded build artifact onto the live app directory without
 * disturbing .env/storage/etc.
 */
class DirectorySync
{
    /**
     * @return array{synced: int, deleted: int}
     */
    public function sync(string $source, string $target, array $excludedPrefixes = []): array
    {
        $sourceFiles = $this->relativeFileList($source);

        foreach ($sourceFiles as $relativePath) {
            if ($this->isExcluded($relativePath, $excludedPrefixes)) {
                continue;
            }

            $from = $source.DIRECTORY_SEPARATOR.$relativePath;
            $to = $target.DIRECTORY_SEPARATOR.$relativePath;

            File::ensureDirectoryExists(dirname($to));
            File::copy($from, $to);
        }

        $targetFiles = $this->relativeFileList($target);
        $sourceSet = array_flip($sourceFiles);
        $deleted = 0;

        foreach ($targetFiles as $relativePath) {
            if ($this->isExcluded($relativePath, $excludedPrefixes)) {
                continue;
            }

            if (! isset($sourceSet[$relativePath])) {
                File::delete($target.DIRECTORY_SEPARATOR.$relativePath);
                $deleted++;
            }
        }

        $this->pruneEmptyDirectories($target, $excludedPrefixes);

        return ['synced' => count($sourceFiles), 'deleted' => $deleted];
    }

    private function isExcluded(string $relativePath, array $excludedPrefixes): bool
    {
        foreach ($excludedPrefixes as $prefix) {
            if ($relativePath === $prefix || str_starts_with($relativePath, $prefix.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[] Paths relative to $root, using DIRECTORY_SEPARATOR.
     */
    private function relativeFileList(string $root): array
    {
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $files[] = ltrim(str_replace($root, '', $fileInfo->getPathname()), DIRECTORY_SEPARATOR);
            }
        }

        return $files;
    }

    private function pruneEmptyDirectories(string $root, array $excludedPrefixes): void
    {
        if (! is_dir($root)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isDir()) {
                continue;
            }

            $relativePath = ltrim(str_replace($root, '', $fileInfo->getPathname()), DIRECTORY_SEPARATOR);

            if ($this->isExcluded($relativePath, $excludedPrefixes)) {
                continue;
            }

            if (count(scandir($fileInfo->getPathname()) ?: []) === 2) {
                @rmdir($fileInfo->getPathname());
            }
        }
    }
}
