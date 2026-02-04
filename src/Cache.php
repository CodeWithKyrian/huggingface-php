<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace;

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Support\CacheManager;
use Codewithkyrian\HuggingFace\Support\RepoId;

/**
 * Public interface for managing the local Hugging Face cache.
 */
final class Cache
{
    public function __construct(
        private readonly CacheManager $manager
    ) {}

    /**
     * List all cached repositories with their details.
     *
     * @return array<int, array{
     *   id: string,
     *   type: string,
     *   size: int,
     *   refs: string[],
     *   path: string
     * }>
     */
    public function list(): array
    {
        $repos = [];
        $cacheDir = $this->manager->getCacheDir();

        if (!is_dir($cacheDir)) {
            return [];
        }

        $pattern = $cacheDir.\DIRECTORY_SEPARATOR.'{models,datasets,spaces}--*';

        foreach (glob($pattern, \GLOB_BRACE) as $path) {
            $dirname = basename($path);
            $parts = explode('--', $dirname);
            $type = rtrim(array_shift($parts), 's'); // 'models' -> 'model'
            $id = implode('/', $parts);

            $repos[] = [
                'id' => $id,
                'type' => $type,
                'path' => $path,
                'size' => $this->calculateSize($path),
                'refs' => $this->scanRefs($path),
            ];
        }

        return $repos;
    }

    /**
     * Delete a repository from the cache.
     *
     * @param string        $repoId   The repository ID (e.g., 'google/bert-base-uncased')
     * @param null|RepoType $repoType The type of repository (defaults to Model)
     */
    public function delete(string $repoId, ?RepoType $repoType = null): void
    {
        $repoType ??= RepoType::Model;
        $id = RepoId::parse($repoId);

        $this->manager->deleteRepo($id, $repoType);
    }

    /**
     * Clear the entire cache.
     *
     * WARNING: This deletes all cached models, datasets, and spaces.
     */
    public function clear(): void
    {
        $this->manager->clearAll();
    }

    /**
     * Scan the refs directory to find available revisions.
     *
     * @return string[] List of refs (branches/tags)
     */
    private function scanRefs(string $repoPath): array
    {
        $refsDir = $repoPath.\DIRECTORY_SEPARATOR.'refs';
        if (!is_dir($refsDir)) {
            return [];
        }

        $refs = [];
        foreach (scandir($refsDir) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            $refs[] = $file;
        }

        return $refs;
    }

    /**
     * Calculate folder size recursively.
     */
    private function calculateSize(string $dir): int
    {
        $size = 0;
        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, $flags));

        foreach ($iterator as $file) {
            try {
                $size += $file->getSize();
            } catch (\RuntimeException) {
                // Ignore files that cannot be stat'ed (e.g., broken symlinks)
            }
        }

        return $size;
    }
}
