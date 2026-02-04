<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Support;

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;

/**
 * Manages repository-based cache storage using blob deduplication and symlinks.
 *
 * This cache system stores files in a structure that matches the JavaScript
 * Hugging Face Hub library, enabling deduplication and efficient snapshot management.
 *
 * Structure:
 * <cacheDir>/<repoFolder>/
 *   ├── blobs/<etag>              # Deduplicated file storage
 *   ├── snapshots/<commitSha>/    # Snapshot pointers (symlinks to blobs)
 *   └── refs/<revision>           # Branch/tag -> commit SHA mappings
 */
final class CacheManager
{
    public function __construct(
        private ?string $cacheDir = null
    ) {
        $this->cacheDir ??= self::defaultCacheDir();

        if (null === $this->cacheDir) {
            throw new \RuntimeException('Cache directory is not configured.');
        }

        Utils::ensureDirectory($this->cacheDir);
    }

    /**
     * Get the configured cache directory.
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * Get the default cache directory based on the operating system.
     *
     * Checks HF_HOME, HF_HUB_CACHE, and OS-specific defaults.
     *
     * @return null|string Cache directory path, or null if cannot be determined
     */
    public static function defaultCacheDir(): ?string
    {
        $hfHubCache = Utils::env('HF_HUB_CACHE');
        if (null !== $hfHubCache) {
            return Utils::ensureDirectory($hfHubCache);
        }

        $hfHome = Utils::env('HF_HOME');
        if (null !== $hfHome) {
            return Utils::ensureDirectory($hfHome.\DIRECTORY_SEPARATOR.'hub');
        }

        $baseDir = match (\PHP_OS_FAMILY) {
            'Windows' => Utils::env('LOCALAPPDATA'),
            'Darwin' => null !== Utils::env('HOME')
            ? Utils::env('HOME').\DIRECTORY_SEPARATOR.'Library'.\DIRECTORY_SEPARATOR.'Caches'
            : null,
            default => Utils::env('XDG_CACHE_HOME')
            ?? (null !== Utils::env('HOME')
                ? Utils::env('HOME').\DIRECTORY_SEPARATOR.'.cache'
                : null),
        };

        if (null === $baseDir) {
            return null;
        }

        return Utils::ensureDirectory(
            $baseDir.\DIRECTORY_SEPARATOR.'huggingface'.\DIRECTORY_SEPARATOR.'hub'
        );
    }

    /**
     * Get the storage folder for a repository.
     *
     * @return string Path to <cacheDir>/<repoFolder>
     */
    public function getStorageFolder(RepoId $repoId, RepoType $repoType): string
    {
        $repoFolder = $this->buildRepoFolderName($repoId, $repoType);

        return $this->cacheDir.\DIRECTORY_SEPARATOR.$repoFolder;
    }

    /**
     * Get the blob path for an etag.
     *
     * @return string Path to <storageFolder>/blobs/<etag>
     */
    public function getBlobPath(RepoId $repoId, RepoType $repoType, string $etag): string
    {
        $storageFolder = $this->getStorageFolder($repoId, $repoType);

        return $storageFolder.\DIRECTORY_SEPARATOR.'blobs'.\DIRECTORY_SEPARATOR.$etag;
    }

    /**
     * Get the snapshot pointer path for a file.
     *
     * @return string Path to <storageFolder>/snapshots/<commitSha>/<filePath>
     */
    public function getSnapshotPointerPath(
        RepoId $repoId,
        RepoType $repoType,
        string $commitSha,
        string $filePath
    ): string {
        $storageFolder = $this->getStorageFolder($repoId, $repoType);
        $snapshotDir = $storageFolder.\DIRECTORY_SEPARATOR.'snapshots'.\DIRECTORY_SEPARATOR.$commitSha;
        $normalizedPath = ltrim(str_replace(['\\', '/'], \DIRECTORY_SEPARATOR, $filePath), \DIRECTORY_SEPARATOR);

        return $snapshotDir.\DIRECTORY_SEPARATOR.$normalizedPath;
    }

    /**
     * Check if a snapshot pointer exists (following symlinks).
     */
    public function snapshotPointerExists(
        RepoId $repoId,
        RepoType $repoType,
        string $commitSha,
        string $filePath
    ): bool {
        $pointerPath = $this->getSnapshotPointerPath($repoId, $repoType, $commitSha, $filePath);

        return file_exists($pointerPath) || is_link($pointerPath);
    }

    /**
     * Check if a blob exists.
     */
    public function blobExists(RepoId $repoId, RepoType $repoType, string $etag): bool
    {
        $blobPath = $this->getBlobPath($repoId, $repoType, $etag);

        return file_exists($blobPath);
    }

    /**
     * Create a symlink from pointer to blob (or copy on Windows if symlinks fail).
     *
     * @throws \RuntimeException If pointer creation fails
     */
    public function createPointer(
        RepoId $repoId,
        RepoType $repoType,
        string $commitSha,
        string $filePath,
        string $etag
    ): void {
        $pointerPath = $this->getSnapshotPointerPath($repoId, $repoType, $commitSha, $filePath);
        $blobPath = $this->getBlobPath($repoId, $repoType, $etag);

        if (!file_exists($blobPath)) {
            throw new \RuntimeException("Blob does not exist: {$blobPath}");
        }

        Utils::ensureDirectory(\dirname($pointerPath));

        if (file_exists($pointerPath) || is_link($pointerPath)) {
            @unlink($pointerPath);
        }

        $relativeBlobPath = $this->getRelativePath($pointerPath, $blobPath);

        if (\PHP_OS_FAMILY === 'Windows') {
            if (!copy($blobPath, $pointerPath)) {
                throw new \RuntimeException("Failed to copy blob to pointer: {$pointerPath}");
            }
        } else {
            if (!symlink($relativeBlobPath, $pointerPath)) {
                $absoluteBlobPath = realpath($blobPath);
                if (false !== $absoluteBlobPath && symlink($absoluteBlobPath, $pointerPath)) {
                    return;
                }

                if (!copy($blobPath, $pointerPath)) {
                    throw new \RuntimeException("Failed to create symlink or copy: {$pointerPath}");
                }
            }
        }
    }

    /**
     * Store a ref (branch/tag -> commit SHA mapping).
     */
    public function storeRef(RepoId $repoId, RepoType $repoType, string $revision, string $commitSha): void
    {
        $storageFolder = $this->getStorageFolder($repoId, $repoType);
        $refsDir = $storageFolder.\DIRECTORY_SEPARATOR.'refs';
        Utils::ensureDirectory($refsDir);

        $refPath = $refsDir.\DIRECTORY_SEPARATOR.$revision;
        file_put_contents($refPath, $commitSha);
    }

    /**
     * Resolve a revision to a commit SHA (from refs or return as-is if already a SHA).
     *
     * @return null|string Commit SHA if found, null if not cached
     */
    public function resolveRevision(
        RepoId $repoId,
        RepoType $repoType,
        string $revision
    ): ?string {
        if (preg_match('/^[0-9a-f]{40}$/i', $revision)) {
            return $revision;
        }

        $storageFolder = $this->getStorageFolder($repoId, $repoType);
        $refPath = $storageFolder.\DIRECTORY_SEPARATOR.'refs'.\DIRECTORY_SEPARATOR.$revision;

        if (file_exists($refPath)) {
            $commitSha = trim(file_get_contents($refPath));
            if (preg_match('/^[0-9a-f]{40}$/i', $commitSha)) {
                return $commitSha;
            }
        }

        return null;
    }

    /**
     * Get the snapshot directory path for a commit SHA.
     *
     * @return string Path to <storageFolder>/snapshots/<commitSha>
     */
    public function getSnapshotDir(RepoId $repoId, RepoType $repoType, string $commitSha): string
    {
        $storageFolder = $this->getStorageFolder($repoId, $repoType);

        return $storageFolder.\DIRECTORY_SEPARATOR.'snapshots'.\DIRECTORY_SEPARATOR.$commitSha;
    }

    /**
     * Get the manifest path for a specific commit.
     * format: <cacheDir>/<repoFolder>/manifests/<commitSha>.json.
     */
    public function getManifestPath(RepoId $repoId, RepoType $repoType, string $commitSha): string
    {
        $storageFolder = $this->getStorageFolder($repoId, $repoType);

        return $storageFolder.\DIRECTORY_SEPARATOR.'manifests'.\DIRECTORY_SEPARATOR.$commitSha.'.json';
    }

    /**
     * Check if a manifest exists for this commit.
     */
    public function manifestExists(RepoId $repoId, RepoType $repoType, string $commitSha): bool
    {
        return file_exists($this->getManifestPath($repoId, $repoType, $commitSha));
    }

    /**
     * Load the manifest (list of files) for a commit.
     * Returns null if not found.
     *
     * @return null|string[]
     */
    public function loadManifest(RepoId $repoId, RepoType $repoType, string $commitSha): ?array
    {
        $path = $this->getManifestPath($repoId, $repoType, $commitSha);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return json_decode($content, true);
    }

    /**
     * Save the manifest.
     *
     * @param string[] $fileList
     */
    public function saveManifest(RepoId $repoId, RepoType $repoType, string $commitSha, array $fileList): void
    {
        $path = $this->getManifestPath($repoId, $repoType, $commitSha);
        Utils::ensureDirectory(\dirname($path));

        file_put_contents($path, json_encode($fileList));
    }

    /**
     * Delete a specific repository from the cache.
     *
     * @param RepoId   $repoId   The repository ID
     * @param RepoType $repoType The repository type
     */
    public function deleteRepo(RepoId $repoId, RepoType $repoType): void
    {
        $folderName = $this->buildRepoFolderName($repoId, $repoType);
        $path = $this->cacheDir.\DIRECTORY_SEPARATOR.$folderName;

        if (is_dir($path)) {
            $this->recursiveDelete($path);
        }
    }

    /**
     * Clear the ENTIRE Hugging Face cache.
     *
     * Deletes all models, datasets, and spaces in the cache directory.
     */
    public function clearAll(): void
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }

        // Use a whitelist pattern to verify we are only deleting HF folders
        $pattern = $this->cacheDir.\DIRECTORY_SEPARATOR.'{models,datasets,spaces}--*';

        foreach (glob($pattern, \GLOB_BRACE) as $path) {
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            }
        }
    }

    /**
     * Build repo folder name: models--openai-community--gpt2.
     */
    private function buildRepoFolderName(RepoId $repoId, RepoType $repoType): string
    {
        $typePrefix = match ($repoType) {
            RepoType::Model => 'models',
            RepoType::Dataset => 'datasets',
            RepoType::Space => 'spaces',
        };

        $parts = [$typePrefix, ...explode('/', $repoId->toUrlPath())];

        return implode('--', $parts);
    }

    /**
     * Get relative path from one file to another.
     */
    private function getRelativePath(string $from, string $to): string
    {
        $fromDir = \dirname($from);
        $toDir = \dirname($to);
        $toFile = basename($to);

        $fromReal = realpath($fromDir);
        $toReal = realpath($toDir);

        if (false === $fromReal || false === $toReal) {
            $fromParts = explode(\DIRECTORY_SEPARATOR, $fromDir);
            $toParts = explode(\DIRECTORY_SEPARATOR, $toDir);
        } else {
            $fromParts = explode(\DIRECTORY_SEPARATOR, $fromReal);
            $toParts = explode(\DIRECTORY_SEPARATOR, $toReal);
        }

        while (!empty($fromParts) && !empty($toParts) && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $relative = str_repeat('..'.\DIRECTORY_SEPARATOR, \count($fromParts));
        if (!empty($toParts)) {
            $relative .= implode(\DIRECTORY_SEPARATOR, $toParts).\DIRECTORY_SEPARATOR;
        }
        $relative .= $toFile;

        return $relative;
    }

    private function recursiveDelete(string $dir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getPathname());
        }

        rmdir($dir);
    }
}
