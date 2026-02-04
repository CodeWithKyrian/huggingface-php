<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\DTOs\CommitOpAdd;
use Codewithkyrian\HuggingFace\Hub\DTOs\CommitOpDelete;
use Codewithkyrian\HuggingFace\Hub\DTOs\CommitOperation;
use Codewithkyrian\HuggingFace\Hub\DTOs\CommitOutput;
use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Support\RepoId;

/**
 * Fluent builder for creating commits.
 */
final class CommitBuilder
{
    /** @var CommitOperation[] */
    private array $operations = [];

    private string $revision = 'main';
    private ?string $parentCommit = null;
    private ?string $description = null;
    private bool $createPr = false;

    public function __construct(
        private readonly string $hubUrl,
        private readonly HttpConnector $http,
        private readonly RepoId $repoId,
        private readonly RepoType $repoType,
        private readonly string $commitMessage,
    ) {}

    /**
     * Add a file to the commit.
     *
     * @param string          $path    Path in the repo
     * @param resource|string $content Content to upload (string content, file path, URL, or resource)
     */
    public function addFile(string $path, mixed $content): self
    {
        $clone = clone $this;

        if (\is_string($content)) {
            if (file_exists($content)) {
                $resource = fopen($content, 'r');
                if (false === $resource) {
                    throw new \RuntimeException("Failed to read file: {$content}");
                }
                $content = $resource;
            } elseif (filter_var($content, \FILTER_VALIDATE_URL)) {
                $remoteResource = fopen($content, 'r');
                if (false === $remoteResource) {
                    throw new \RuntimeException("Failed to open URL: {$content}");
                }

                // Copy to a seekable temp stream
                $temp = fopen('php://temp', 'r+');
                stream_copy_to_stream($remoteResource, $temp);
                rewind($temp);
                fclose($remoteResource);

                $content = $temp;
            }
        }

        $clone->operations[] = new CommitOpAdd($path, $content);

        return $clone;
    }

    /**
     * Delete a file.
     */
    public function deleteFile(string $path): self
    {
        $clone = clone $this;
        $clone->operations[] = new CommitOpDelete($path);

        return $clone;
    }

    /**
     * Target a specific branch or revision.
     */
    public function branch(string $branch): self
    {
        $clone = clone $this;
        $clone->revision = $branch;

        return $clone;
    }

    /**
     * Set the parent commit.
     *
     * - When opening a PR: will use parentCommit as the parent commit
     * - When committing on a branch: Will make sure that there were no intermediate commits
     */
    public function parentCommit(string $parentCommit): self
    {
        $clone = clone $this;
        $clone->parentCommit = $parentCommit;

        return $clone;
    }

    /**
     * Set the commit description.
     */
    public function description(string $description): self
    {
        $clone = clone $this;
        $clone->description = $description;

        return $clone;
    }

    /**
     * Create a Pull Request instead of committing directly to the branch.
     */
    public function createPr(bool $createPr = true): self
    {
        $clone = clone $this;
        $clone->createPr = $createPr;

        return $clone;
    }

    /**
     * Execute the commit.
     */
    public function push(): CommitOutput
    {
        $lfsFiles = $this->preupload();

        if (!empty($lfsFiles)) {
            $this->uploadLfsFiles($lfsFiles);
        }

        return $this->finalizeCommit($lfsFiles);
    }

    /**
     * Preupload to identify LFS files.
     *
     * @return array<string, string> Map of path => sha256 for LFS files
     */
    private function preupload(): array
    {
        $filesToUpload = [];

        foreach ($this->operations as $op) {
            if ($op instanceof CommitOpAdd) {
                // We need a sample (first 512 bytes)
                $content = $op->getContent();
                $sample = '';

                if (\is_resource($content)) {
                    $pos = ftell($content);
                    $sample = fread($content, 512);
                    fseek($content, $pos); // Reset pointer
                } else {
                    $sample = substr($content, 0, 512);
                }

                $filesToUpload[] = [
                    'path' => $op->getPath(),
                    'size' => $op->getSize(),
                    'sample' => base64_encode($sample),
                ];
            }
        }

        if (empty($filesToUpload)) {
            return [];
        }

        $url = \sprintf(
            '%s/api/%s/%s/preupload/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            rawurlencode($this->revision)
        );

        if ($this->createPr) {
            $url .= '?create_pr=1';
        }

        $response = $this->http->post($url, [
            'files' => $filesToUpload,
        ]);

        $json = $response->json();
        $lfsFiles = [];

        foreach ($json['files'] as $file) {
            if (($file['uploadMode'] ?? '') === 'lfs') {
                $lfsFiles[$file['path']] = ''; // Will calculate SHA later
            }
        }

        return $lfsFiles;
    }

    /**
     * Upload LFS files.
     *
     * @param array<string, string> $lfsFiles
     */
    private function uploadLfsFiles(array &$lfsFiles): void
    {
        $objects = [];
        $opsByPath = [];

        foreach ($this->operations as $op) {
            if ($op instanceof CommitOpAdd && \array_key_exists($op->getPath(), $lfsFiles)) {
                $sha = $this->calculateSha256($op->getContent());
                $lfsFiles[$op->getPath()] = $sha;

                $objects[] = [
                    'oid' => $sha,
                    'size' => $op->getSize(),
                ];
                $opsByPath[$op->getPath()] = $op;
            }
        }

        $url = \sprintf(
            '%s/%s%s.git/info/lfs/objects/batch',
            $this->hubUrl,
            RepoType::Model === $this->repoType ? '' : $this->repoType->apiPath().'/',
            $this->repoId->toUrlPath()
        );

        $payload = [
            'operation' => 'upload',
            'transfers' => ['basic', 'multipart'],
            'objects' => $objects,
            'hash_algo' => 'sha_256',
        ];

        if (!$this->createPr) {
            $payload['ref'] = ['name' => $this->revision];
        }

        $response = $this->http->post($url, $payload, [
            'Accept' => 'application/vnd.git-lfs+json',
            'Content-Type' => 'application/vnd.git-lfs+json',
        ]);

        $batchResponse = $response->json();

        foreach ($batchResponse['objects'] as $obj) {
            if (isset($obj['error'])) {
                throw new \RuntimeException('LFS Error: '.$obj['error']['message']);
            }

            if (!isset($obj['actions']['upload'])) {
                // Already uploaded
                continue;
            }

            $uploadAction = $obj['actions']['upload'];
            $oid = $obj['oid'];

            foreach ($lfsFiles as $path => $sha) {
                if ($sha !== $oid) {
                    return;
                }

                $op = $opsByPath[$path];
                $content = $op->getContent();
                $url = $uploadAction['href'];
                $headers = $uploadAction['header'] ?? [];

                $this->http->put($url, $content, $headers);
            }
        }
    }

    private function calculateSha256(mixed $content): string
    {
        $ctx = hash_init('sha256');

        if (\is_resource($content)) {
            $pos = ftell($content);
            rewind($content);
            hash_update_stream($ctx, $content);
            fseek($content, $pos);
        } else {
            hash_update($ctx, $content);
        }

        return hash_final($ctx);
    }

    private function buildCommitHeader(): string
    {
        $header = [
            'summary' => $this->commitMessage,
        ];

        if (null !== $this->description) {
            $header['description'] = $this->description;
        }

        if (null !== $this->parentCommit) {
            $header['parentCommit'] = $this->parentCommit;
        }

        return json_encode([
            'key' => 'header',
            'value' => $header,
        ]);
    }

    /**
     * Finalize Commit (NDJSON).
     *
     * @param array<string, string> $lfsFiles Path => SHA map
     */
    private function finalizeCommit(array $lfsFiles): CommitOutput
    {
        $url = \sprintf(
            '%s/api/%s/%s/commit/%s',
            $this->hubUrl,
            $this->repoType->apiPath(),
            $this->repoId->toUrlPath(),
            rawurlencode($this->revision)
        );

        if ($this->createPr) {
            $url .= '?create_pr=1';
        }

        $lines = [
            $this->buildCommitHeader(),
        ];

        foreach ($this->operations as $op) {
            $lines[] = $op->toNdJson($lfsFiles);
        }

        $ndjsonBody = implode("\n", $lines);

        $response = $this->http->post($url, $ndjsonBody, [
            'Content-Type' => 'application/x-ndjson',
        ]);

        return CommitOutput::fromArray($response->json());
    }
}
