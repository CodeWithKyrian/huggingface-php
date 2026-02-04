<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Builders;

use Codewithkyrian\HuggingFace\Http\CurlConnector;
use Codewithkyrian\HuggingFace\Http\DownloadPartHandler;
use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\DTOs\DatasetInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\FileDownloadInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\ModelInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\PathInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\SpaceInfo;
use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Support\CacheManager;
use Codewithkyrian\HuggingFace\Support\RepoId;
use Codewithkyrian\HuggingFace\Support\Utils;

/**
 * Fluent builder for downloading files from the Hub.
 *
 * Supports caching, streaming downloads, progress callbacks, and resumable downloads.
 */
final class DownloadBuilder
{
    private bool $useCache = true;
    private bool $forceDownload = false;
    private bool $resumable = true;

    /** @var null|callable(int, int): void */
    private $progressCallback;

    public function __construct(
        private readonly string $hubUrl,
        private readonly HttpConnector $http,
        private readonly CurlConnector $curl,
        private readonly CacheManager $cache,
        private readonly RepoId $repoId,
        private readonly string $filename,
        private readonly RepoType $repoType = RepoType::Model,
        private readonly string $revision = 'main',
    ) {}

    /**
     * Enable or disable caching for this download.
     */
    public function useCache(bool $useCache = true): self
    {
        $clone = clone $this;
        $clone->useCache = $useCache;

        return $clone;
    }

    /**
     * Force re-download even if cached.
     */
    public function force(bool $force = true): self
    {
        $clone = clone $this;
        $clone->forceDownload = $force;

        return $clone;
    }

    /**
     * Enable resumable downloads.
     *
     * If a partial download exists, it will resume from where it left off.
     * Uses HTTP Range headers for efficient resume.
     */
    public function resumable(bool $resumable = true): self
    {
        $clone = clone $this;
        $clone->resumable = $resumable;

        return $clone;
    }

    /**
     * Set a progress callback for tracking download progress.
     *
     * @param callable(int, int): void $callback Receives (bytesDownloaded, totalBytes)
     */
    public function onProgress(callable $callback): self
    {
        $clone = clone $this;
        $clone->progressCallback = $callback;

        return $clone;
    }

    /**
     * Download file to a directory, returning the local path.
     *
     * When caching is enabled, saves to repo cache first, then copies to the target directory.
     * Always streams directly to disk without loading into memory.
     * Supports resumable downloads when resumable() is enabled.
     */
    /**
     * Download file to a directory, returning the local path.
     *
     * If a directory is provided, it tries to save to cache first, then copies to the target directory.
     * If no directory is provided, it saves to the repo cache and returns the cached path.
     * Always streams directly to disk without loading into memory.
     *
     * @param null|string $directory The directory to save the file to. If null, saves to cache only.
     *
     * @return string the path to the saved file (local or cached)
     */
    public function save(?string $directory = null): string
    {
        $localPath = $directory ? $this->buildLocalPath($directory) : null;

        if ($localPath) {
            Utils::ensureDirectory(\dirname($localPath));
        }

        if ($this->useCache) {
            try {
                $cachedPath = $this->saveToCache();

                if (null === $localPath) {
                    return $cachedPath;
                }

                copy($cachedPath, $localPath);

                return $localPath;
            } catch (\Exception $e) {
                if (null === $localPath) {
                    throw $e;
                }
            }
        }

        if (null === $localPath) {
            throw new \RuntimeException('Cannot save file: Cache is disabled or failed, and no output directory provided.');
        }

        if (!$this->forceDownload && file_exists($localPath)) {
            $info = $this->info();
            if (filesize($localPath) === $info->size) {
                return $localPath;
            }
        }

        $this->downloadToPath($localPath);

        if (!file_exists($localPath)) {
            throw new \RuntimeException("Download failed: Final file not found at {$localPath}");
        }

        return $localPath;
    }

    /**
     * Download and return the file content as a string.
     *
     * Loads the entire file into memory. For large files, use save() instead.
     */
    public function getContent(): string
    {
        if ($this->useCache && !$this->forceDownload) {
            $commitSha = $this->cache->resolveRevision($this->repoId, $this->repoType, $this->revision);

            if (null !== $commitSha) {
                $cachedPath = $this->cache->getSnapshotPointerPath(
                    $this->repoId,
                    $this->repoType,
                    $commitSha,
                    $this->filename
                );

                if (file_exists($cachedPath) || is_link($cachedPath)) {
                    $content = file_get_contents($cachedPath);
                    if (false !== $content) {
                        return $content;
                    }
                }
            }
        }

        $url = $this->buildUrl();
        $response = $this->http->get($url);
        $content = $response->body();

        if ($this->useCache) {
            try {
                $this->saveToCache();
            } catch (\Exception $e) {
            }
        }

        return $content;
    }

    /**
     * Download and decode as JSON.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException If JSON decoding fails
     */
    public function json(): array
    {
        $content = $this->getContent();
        $data = json_decode($content, true);

        if (\JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Failed to decode JSON: '.json_last_error_msg());
        }

        return $data;
    }

    /**
     * Get file download metadata without downloading the file.
     */
    public function info(): FileDownloadInfo
    {
        $url = $this->buildUrl();

        $response = $this->http->get($url, [], [
            'Range' => 'bytes=0-0',
        ]);

        $contentRange = $response->header('content-range');
        $size = 0;

        if (null !== $contentRange && preg_match('/\/(\d+)$/', $contentRange, $matches)) {
            $size = (int) $matches[1];
        } elseif (200 === $response->status()) {
            $size = (int) $response->header('content-length');
        }

        $etag = $response->header('x-linked-etag') ?? $response->header('etag') ?? '';

        return new FileDownloadInfo(
            size: $size,
            etag: $etag,
            url: $url,
        );
    }

    /**
     * Check if the file exists in cache.
     */
    public function isCached(): bool
    {
        $commitSha = $this->cache->resolveRevision($this->repoId, $this->repoType, $this->revision);

        if (null === $commitSha) {
            return false;
        }

        return $this->cache->snapshotPointerExists(
            $this->repoId,
            $this->repoType,
            $commitSha,
            $this->filename
        );
    }

    /**
     * Download file to the repo cache directory using blob storage.
     *
     * This method uses the unified repo cache system with blob deduplication:
     * 1. Gets file info (including oid/lfs.oid for etag)
     * 2. Checks if pointer already exists in snapshot -> return early
     * 3. Checks if blob exists -> create symlink and return
     * 4. Downloads blob if needed
     * 5. Creates symlink from snapshot pointer to blob
     *
     * @return string Path to the snapshot pointer (symlink)
     *
     * @throws \RuntimeException If cache directory is not configured or file not found
     */
    private function saveToCache(): string
    {
        $commitSha = $this->cache->resolveRevision($this->repoId, $this->repoType, $this->revision);

        if ($commitSha) {
            $pointerPath = $this->cache->getSnapshotPointerPath(
                $this->repoId,
                $this->repoType,
                $commitSha,
                $this->filename
            );

            if (file_exists($pointerPath)) {
                return $pointerPath;
            }
        }

        $pathInfo = $this->getPathInfo();
        if (null === $pathInfo) {
            throw new \RuntimeException("File not found: {$this->filename}");
        }

        $etag = $pathInfo->lfs->oid ?? $pathInfo->oid;

        if (!$commitSha) {
            $commitSha = $this->cache->resolveRevision($this->repoId, $this->repoType, $this->revision);
            if (null === $commitSha) {
                $repoInfo = $this->getRepoInfo();
                $commitSha = $repoInfo->sha ?? $this->revision;
            }
        }

        $pointerPath = $this->cache->getSnapshotPointerPath(
            $this->repoId,
            $this->repoType,
            $commitSha,
            $this->filename
        );

        if ($this->cache->snapshotPointerExists($this->repoId, $this->repoType, $commitSha, $this->filename)) {
            return $pointerPath;
        }

        $blobPath = $this->cache->getBlobPath($this->repoId, $this->repoType, $etag);
        Utils::ensureDirectory(\dirname($blobPath));

        if ($this->cache->blobExists($this->repoId, $this->repoType, $etag)) {
            $this->cache->createPointer($this->repoId, $this->repoType, $commitSha, $this->filename, $etag);

            return $pointerPath;
        }

        $this->downloadToPath($blobPath);

        if (!file_exists($blobPath)) {
            throw new \RuntimeException("Download failed: Blob not found at {$blobPath}");
        }

        $this->cache->createPointer($this->repoId, $this->repoType, $commitSha, $this->filename, $etag);

        if ($this->revision !== $commitSha) {
            $this->cache->storeRef($this->repoId, $this->repoType, $this->revision, $commitSha);
        }

        return $pointerPath;
    }

    /**
     * Build the local file path.
     */
    private function buildLocalPath(string $directory): string
    {
        return rtrim($directory, \DIRECTORY_SEPARATOR)
            .\DIRECTORY_SEPARATOR
            .Utils::sanitizeFilename($this->filename);
    }

    /**
     * Build the download URL.
     */
    private function buildUrl(): string
    {
        $filePath = implode('/', array_map('rawurlencode', explode('/', $this->filename)));
        $revision = rawurlencode($this->revision);

        return \sprintf(
            '%s/%s/resolve/%s/%s',
            $this->hubUrl,
            $this->repoId->toUrlPath(),
            $revision,
            $filePath
        );
    }

    /**
     * Get PathInfo for the current file by calling the paths-info API.
     *
     * @return null|PathInfo Path info if file exists, null otherwise
     */
    private function getPathInfo(): ?PathInfo
    {
        $url = \sprintf(
            '%s/api/%s/%s/paths-info/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            rawurlencode($this->revision)
        );

        $response = $this->http->post($url, [
            'paths' => [$this->filename],
            'expand' => true,
        ]);

        $data = $response->json();
        if (empty($data)) {
            return null;
        }

        $pathData = $data[0] ?? null;
        if (!\is_array($pathData)) {
            return null;
        }

        return PathInfo::fromArray($pathData);
    }

    /**
     * Get repository info to extract commit SHA.
     *
     * @return DatasetInfo|ModelInfo|SpaceInfo Repository info
     */
    private function getRepoInfo(): DatasetInfo|ModelInfo|SpaceInfo
    {
        $revision = 'main' === $this->revision ? 'HEAD' : $this->revision;
        $url = \sprintf(
            '%s/api/%s/%s/revision/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            rawurlencode($revision)
        );

        $query = ['expand' => ['sha']];
        $response = $this->http->get($url, $query);
        $data = $response->json();

        return match ($this->repoType) {
            RepoType::Model => ModelInfo::fromArray($data),
            RepoType::Dataset => DatasetInfo::fromArray($data),
            RepoType::Space => SpaceInfo::fromArray($data),
        };
    }

    /**
     * Download file to a specific path using the configured download settings.
     *
     * Handles resumable downloads, progress callbacks, and part management.
     *
     * @param string $path Path where the file should be downloaded
     *
     * @throws \RuntimeException If download fails
     */
    private function downloadToPath(string $path): void
    {
        $info = $this->info();
        $partManager = new DownloadPartHandler($path);

        if ($this->forceDownload || !$this->resumable) {
            $partManager->cleanup();
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        if ($this->resumable) {
            $this->resumeDownload($partManager, $info);
        } else {
            $this->freshDownload($partManager, $info);
        }

        $partManager->finalize($path);
    }

    private function freshDownload(DownloadPartHandler $partManager, FileDownloadInfo $info): void
    {
        $partManager->cleanup();
        $partPath = $partManager->createPart(0);

        $this->curl->download(
            $info->url,
            $partPath,
            $info->size,
            0,
            $this->progressCallback
        );
    }

    private function resumeDownload(DownloadPartHandler $partManager, FileDownloadInfo $info): void
    {
        $downloadedBytes = $partManager->getTotalDownloadedBytes();

        if ($downloadedBytes >= $info->size) {
            return;
        }

        $partIndex = $partManager->getNextPartIndex();
        $partPath = $partManager->createPart($partIndex);

        $this->curl->download(
            $info->url,
            $partPath,
            $info->size,
            $downloadedBytes,
            $this->progressCallback
        );
    }
}
