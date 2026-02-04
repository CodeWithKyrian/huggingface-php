<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Managers;

use Codewithkyrian\HuggingFace\Exceptions\AuthenticationException;
use Codewithkyrian\HuggingFace\Http\CurlConnector;
use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\Builders\CommitBuilder;
use Codewithkyrian\HuggingFace\Hub\Builders\DownloadBuilder;
use Codewithkyrian\HuggingFace\Hub\DTOs\CommitOutput;
use Codewithkyrian\HuggingFace\Hub\DTOs\DatasetInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\GitRef;
use Codewithkyrian\HuggingFace\Hub\DTOs\GitRefs;
use Codewithkyrian\HuggingFace\Hub\DTOs\ModelInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\PathInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\RepoCommit;
use Codewithkyrian\HuggingFace\Hub\DTOs\RepositoryInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\SpaceInfo;
use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Hub\Enums\Visibility;
use Codewithkyrian\HuggingFace\Hub\Exceptions\ApiException;
use Codewithkyrian\HuggingFace\Hub\Exceptions\NotFoundException;
use Codewithkyrian\HuggingFace\Support\CacheManager;
use Codewithkyrian\HuggingFace\Support\RepoId;
use Codewithkyrian\HuggingFace\Support\Utils;

/**
 * Manager for repository operations on a specific repository.
 */
final class RepoManager
{
    public function __construct(
        public readonly string $hubUrl,
        public readonly HttpConnector $http,
        public readonly CurlConnector $curl,
        public readonly CacheManager $cache,
        public readonly RepoId $repoId,
        public readonly RepoType $repoType = RepoType::Model,
        private readonly string $revision = 'main',
    ) {}

    /**
     * Create a new RepoManager instance for a different revision.
     *
     * @param string $revision Branch, tag, or commit hash
     *
     * @return RepoManager New instance with the specified revision
     */
    public function revision(string $revision): self
    {
        return new self($this->hubUrl, $this->http, $this->curl, $this->cache, $this->repoId, $this->repoType, $revision);
    }

    public function forId(RepoId $repoId): self
    {
        return new self($this->hubUrl, $this->http, $this->curl, $this->cache, $repoId, $this->repoType, $this->revision);
    }

    /**
     * Get repository information.
     *
     * @param string[] $expand Additional fields to expand (e.g. ['siblings', 'sha'])
     */
    public function info(array $expand = []): DatasetInfo|ModelInfo|SpaceInfo
    {
        $revision = 'main' === $this->revision ? 'HEAD' : $this->revision;
        $url = \sprintf(
            '%s/api/%s/%s/revision/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            rawurlencode($revision)
        );

        $query = [];
        if (!empty($expand)) {
            $query['expand'] = $expand;
        }

        $response = $this->http->get($url, $query);
        $data = $response->json();

        return match ($this->repoType) {
            RepoType::Model => ModelInfo::fromArray($data),
            RepoType::Dataset => DatasetInfo::fromArray($data),
            RepoType::Space => SpaceInfo::fromArray($data),
        };
    }

    /**
     * List files in the repository.
     *
     * @param bool        $recursive Whether to list files recursively
     * @param bool        $expand    Whether to expand details (size, last modified, etc.)
     * @param null|string $path      Path to list files from
     *
     * @return \Generator<PathInfo>
     */
    public function files(bool $recursive = false, bool $expand = false, ?string $path = null): \Generator
    {
        $url = \sprintf(
            '%s/api/%s/%s/tree/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            rawurlencode($this->revision)
        );

        if (null !== $path) {
            $url .= '/'.implode('/', array_map('rawurlencode', explode('/', $path)));
        }

        $query = [];
        if ($recursive) {
            $query['recursive'] = 'true';
        }
        if ($expand) {
            $query['expand'] = 'true';
        }

        $url = Utils::buildUrl($url, $query);

        while ($url) {
            $response = $this->http->get($url);
            $items = $response->json();

            foreach ($items as $item) {
                yield PathInfo::fromArray($item);
            }

            $linkHeader = $response->header('Link');
            $url = null;

            if ($linkHeader) {
                $links = Utils::parseLinkHeader($linkHeader);
                $url = $links['next'] ?? null;
            }
        }
    }

    /**
     * List all git references (branches, tags, converts) in the repository.
     *
     * @return GitRefs Collection containing branches, tags, and converts
     */
    public function refs(): GitRefs
    {
        $url = \sprintf(
            '%s/api/%s/%s/refs',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath()
        );

        $response = $this->http->get($url);

        return GitRefs::fromArray($response->json());
    }

    /**
     * List all branches in the repository.
     *
     * @return GitRef[]
     */
    public function branches(): array
    {
        return $this->refs()->branches;
    }

    /**
     * List all tags in the repository.
     *
     * @return GitRef[]
     */
    public function tags(): array
    {
        return $this->refs()->tags;
    }

    /**
     * List commits in the repository.
     *
     * @param int $batchSize Maximum number of commits to fetch per request (1-1000)
     *
     * @return \Generator<RepoCommit>
     */
    public function commits(int $batchSize = 100): \Generator
    {
        $url = \sprintf(
            '%s/api/%s/%s/commits/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            rawurlencode($this->revision)
        );

        $url = Utils::buildUrl($url, ['limit' => min($batchSize, 1000)]);

        while ($url) {
            $response = $this->http->get($url);
            $items = $response->json();

            foreach ($items as $item) {
                yield RepoCommit::fromArray($item);
            }

            $linkHeader = $response->header('Link');
            $url = null;

            if ($linkHeader) {
                $links = Utils::parseLinkHeader($linkHeader);
                $url = $links['next'] ?? null;
            }
        }
    }

    /**
     * Count the total number of commits in the repository.
     *
     * @return int Total number of commits
     */
    public function commitCount(): int
    {
        $url = \sprintf(
            '%s/api/%s/%s/commits/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            rawurlencode($this->revision)
        );

        $response = $this->http->get($url, ['limit' => 1]);

        $totalCount = $response->header('x-total-count');

        return null !== $totalCount ? (int) $totalCount : 0;
    }

    /**
     * Check if a file exists in the repository.
     *
     * @param string $path Path to the file in the repository
     *
     * @return bool True if the file exists, false otherwise
     */
    public function fileExists(string $path): bool
    {
        try {
            $commitSha = $this->cache->resolveRevision($this->repoId, $this->repoType, $this->revision);

            if (!$commitSha) {
                $info = $this->info(expand: ['sha']);
                $commitSha = $info->sha;
            }

            if ($commitSha && $this->cache->manifestExists($this->repoId, $this->repoType, $commitSha)) {
                $manifest = $this->cache->loadManifest($this->repoId, $this->repoType, $commitSha);

                return \in_array($path, $manifest, true);
            }
        } catch (\Exception) {
        }

        try {
            $prefix = RepoType::Model === $this->repoType ? '' : $this->repoType->apiPath().'/';
            $url = \sprintf(
                '%s/%s%s/raw/%s/%s',
                $this->hubUrl,
                $prefix,
                $this->repoId->toUrlPath(),
                rawurlencode($this->revision),
                implode('/', array_map('rawurlencode', explode('/', $path)))
            );

            $this->http->head($url);

            return true;
        } catch (NotFoundException) {
            return false;
        }
    }

    /**
     * Get detailed information about multiple paths in the repository.
     *
     * @param string[] $paths  List of file paths to get info for
     * @param bool     $expand Include last commit and security status for each path
     *
     * @return PathInfo[]
     */
    public function pathsInfo(array $paths, bool $expand = false): array
    {
        $url = \sprintf(
            '%s/api/%s/%s/paths-info/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            rawurlencode($this->revision)
        );

        $response = $this->http->post($url, [
            'paths' => $paths,
            'expand' => $expand,
        ]);

        return array_map(
            static fn (array $item) => PathInfo::fromArray($item),
            $response->json()
        );
    }

    /**
     * Get detailed information about a single file.
     *
     * Convenience method that wraps pathsInfo() for single file access.
     * Returns null if the file doesn't exist.
     *
     * @param string $path   The file path
     * @param bool   $expand Include last commit and security status
     */
    public function fileInfo(string $path, bool $expand = true): ?PathInfo
    {
        $results = $this->pathsInfo([$path], $expand);

        return $results[0] ?? null;
    }

    /**
     * Download a file from the repository.
     */
    public function download(string $filename): DownloadBuilder
    {
        return new DownloadBuilder(
            $this->hubUrl,
            $this->http,
            $this->curl,
            $this->cache,
            $this->repoId,
            $filename,
            $this->repoType,
            $this->revision
        );
    }

    /**
     * Download a full snapshot of the repository.
     *
     * Uses the unified repo cache system with blob deduplication. Files are stored
     * in blobs/ and symlinked from snapshots/<commitSha>/ for efficient storage.
     *
     * @param null|string[] $allowPatterns  only download files matching these patterns
     * @param null|string[] $ignorePatterns skip files matching these patterns
     * @param bool          $force          Whether to force a check for the latest revision. If false, a cached revision will be used if available.
     *
     * @return string absolute path to the snapshot folder
     */
    public function snapshot(?array $allowPatterns = null, ?array $ignorePatterns = null, bool $force = false): string
    {
        $commitSha = null;

        if (!$force) {
            $commitSha = $this->cache->resolveRevision($this->repoId, $this->repoType, $this->revision);
        }

        if (!$commitSha) {
            $info = $this->info(expand: ['sha']);
            $commitSha = $info->sha ?? $this->revision;
        }

        $snapshotFolder = $this->cache->getSnapshotDir($this->repoId, $this->repoType, $commitSha);

        if ($this->revision !== $commitSha) {
            $this->cache->storeRef($this->repoId, $this->repoType, $this->revision, $commitSha);
        }

        Utils::ensureDirectory($snapshotFolder);

        $commitRepo = $this->revision($commitSha);
        $allPaths = [];

        if ($this->cache->manifestExists($this->repoId, $this->repoType, $commitSha)) {
            $allPaths = $this->cache->loadManifest($this->repoId, $this->repoType, $commitSha);
        } else {
            $repoFiles = $commitRepo->files(recursive: true);

            foreach ($repoFiles as $file) {
                if ($file->isDirectory()) {
                    continue;
                }

                $allPaths[] = $file->path;
            }

            $this->cache->saveManifest($this->repoId, $this->repoType, $commitSha, $allPaths);
        }

        foreach ($allPaths as $path) {
            if (null !== $allowPatterns && !Utils::fileMatchesPatterns($path, $allowPatterns)) {
                continue;
            }

            if (null !== $ignorePatterns && Utils::fileMatchesPatterns($path, $ignorePatterns)) {
                continue;
            }

            $commitRepo->download($path)
                ->save();
        }

        return $snapshotFolder;
    }

    /**
     * Create a commit builder for uploading files.
     */
    public function commit(string $message): CommitBuilder
    {
        return new CommitBuilder(
            $this->hubUrl,
            $this->http,
            $this->repoId,
            $this->repoType,
            $message,
        );
    }

    /**
     * Upload a single file to the repository.
     *
     * @param string          $repoPath The path in the repository
     * @param resource|string $content  Content, local file path, or URL
     */
    public function uploadFile(string $repoPath, mixed $content, ?string $commitMessage = null): CommitOutput
    {
        $commitMessage ??= "Add {$repoPath}";

        return $this->commit($commitMessage)
            ->addFile($repoPath, $content)
            ->push();
    }

    /**
     * Upload multiple files to the repository in a single commit.
     *
     * @param array<string, mixed> $files Key is repo path, value is content (string, resource, local path, or URL)
     */
    public function uploadFiles(array $files, ?string $commitMessage = null): CommitOutput
    {
        $count = \count($files);
        $commitMessage ??= "Add {$count} files";

        $builder = $this->commit($commitMessage);

        foreach ($files as $repoPath => $content) {
            $builder = $builder->addFile($repoPath, $content);
        }

        return $builder->push();
    }

    /**
     * Delete a file from the repository.
     */
    public function deleteFile(string $path, ?string $commitMessage = null): CommitOutput
    {
        $commitMessage ??= "Delete {$path}";

        return $this->commit($commitMessage)
            ->deleteFile($path)
            ->push();
    }

    /**
     * Delete multiple files from the repository in a single commit.
     *
     * @param string[] $paths List of paths to delete
     */
    public function deleteFiles(array $paths, ?string $commitMessage = null): CommitOutput
    {
        $count = \count($paths);
        $commitMessage ??= "Delete {$count} files";

        $builder = $this->commit($commitMessage);

        foreach ($paths as $path) {
            $builder = $builder->deleteFile($path);
        }

        return $builder->push();
    }

    /**
     * Update repository settings.
     *
     * @param array<string, mixed> $settings
     */
    public function update(array $settings): RepositoryInfo
    {
        $url = \sprintf(
            '%s/api/%s/%s/settings',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath()
        );

        $response = $this->http->put($url, $settings);

        return $this->info();
    }

    /**
     * Change repository visibility.
     */
    public function setVisibility(Visibility $visibility): RepositoryInfo
    {
        return $this->update(['private' => Visibility::Private === $visibility]);
    }

    /**
     * Delete the repository.
     *
     * @param bool $missingOk Don't throw if repo doesn't exist
     */
    public function delete(bool $missingOk = false): void
    {
        try {
            $url = \sprintf('%s/api/repos/delete', $this->hubUrl);

            $payload = [
                'name' => $this->repoId->name,
                'organization' => $this->repoId->owner,
                'type' => $this->repoType->value,
            ];

            $this->http->delete($url, $payload);
        } catch (NotFoundException $e) {
            if (!$missingOk) {
                throw $e;
            }
        }
    }

    /**
     * Check if the repository exists.
     */
    public function exists(): bool
    {
        try {
            $this->info();

            return true;
        } catch (NotFoundException) {
            return false;
        } catch (AuthenticationException) {
            try {
                $url = \sprintf(
                    '%s/api/%s/%s',
                    $this->hubUrl,
                    $this->repoType->apiPath(),
                    $this->repoId->toUrlPath()
                );
                $this->http->head($url);

                return true;
            } catch (\Exception) {
                return false;
            }
        }
    }

    /**
     * Check if we have read access to the repository.
     *
     * This method will throw an ApiException if access is denied:
     * - 401: Authentication required
     * - 403: Access forbidden (private repo, gated model without access)
     * - 404: Repository not found
     *
     * @throws ApiException
     */
    public function checkAccess(): void
    {
        $url = \sprintf(
            '%s/api/%s/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath()
        );

        $this->http->get($url);
    }

    /**
     * Move/rename the repository.
     *
     * @param string $newName New repository name (just the name, not full path)
     *
     * @return RepoManager The updated repository manager
     */
    public function move(string $newName): self
    {
        $url = \sprintf(
            '%s/api/%s/%s/settings',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath()
        );

        $this->http->put($url, ['name' => $newName]);

        $newId = new RepoId($this->repoId->owner, $newName);

        return $this->forId($newId);
    }

    /**
     * Fork the repository.
     *
     * @param null|string $targetNamespace Namespace to fork to (defaults to current user)
     *
     * @return RepoManager The forked repository manager
     */
    public function fork(?string $targetNamespace = null): self
    {
        $url = \sprintf(
            '%s/api/%s/%s/fork',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath()
        );

        $payload = [];
        if (null !== $targetNamespace) {
            $payload['namespace'] = $targetNamespace;
        }

        $response = $this->http->post($url, $payload);
        $data = $response->json();

        $forkedId = $data['id'] ?? $data['repoId'] ?? '';

        return $this->forId(RepoId::parse($forkedId));
    }

    /**
     * Create a new branch in the repository.
     *
     * @param string      $name      The name of the branch to create
     * @param null|string $revision  The revision to create the branch from (defaults to the manager's revision)
     * @param bool        $empty     Create an empty branch with no commits
     * @param bool        $overwrite Overwrite the branch if it already exists
     */
    public function createBranch(string $name, ?string $revision = null, bool $empty = false, bool $overwrite = false): void
    {
        $url = \sprintf(
            '%s/api/%s/%s/branch/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            urlencode($name)
        );

        $body = ['overwrite' => $overwrite];

        $startingPoint = $revision ?? $this->revision;

        if (!$empty) {
            $body['startingPoint'] = $startingPoint;
        }

        if ($empty) {
            $body['emptyBranch'] = true;
        }

        $this->http->post($url, $body);
    }

    /**
     * Delete a branch from the repository.
     *
     * @param string $branch The name of the branch to delete
     */
    public function deleteBranch(string $branch): void
    {
        $url = \sprintf(
            '%s/api/%s/%s/branch/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            urlencode($branch)
        );

        $this->http->delete($url);
    }

    /**
     * Check if a file exists in the snapshot cache.
     *
     * @param string $path The file path in the repository
     *
     * @return bool True if file is cached, false otherwise
     */
    public function isCached(string $path): bool
    {
        $commitSha = $this->cache->resolveRevision($this->repoId, $this->repoType, $this->revision);
        if (null === $commitSha) {
            return false;
        }

        return $this->cache->snapshotPointerExists($this->repoId, $this->repoType, $commitSha, $path);
    }

    /**
     * Get the cached path for a file in snapshot (if it exists).
     *
     * @param string $path The file path in the repository
     *
     * @return null|string Path to cached file, or null if not cached
     */
    public function getCachedPath(string $path): ?string
    {
        $commitSha = $this->cache->resolveRevision($this->repoId, $this->repoType, $this->revision);
        if (null === $commitSha) {
            return null;
        }

        $pointerPath = $this->cache->getSnapshotPointerPath($this->repoId, $this->repoType, $commitSha, $path);

        if (file_exists($pointerPath) || is_link($pointerPath)) {
            return $pointerPath;
        }

        return null;
    }
}
