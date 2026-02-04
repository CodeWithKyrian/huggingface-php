<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents detailed information about a path in a repository.
 */
final readonly class PathInfo extends Resource
{
    /**
     * @param string              $path           The file path
     * @param string              $type           The type ('file' or 'directory')
     * @param string              $oid            The object ID (SHA hash)
     * @param int                 $size           The file size in bytes
     * @param null|BlobLfsInfo    $lfs            LFS pointer info if the file is stored in LFS
     * @param null|LastCommitInfo $lastCommit     Last commit info (when expand=true)
     * @param null|SecurityStatus $securityStatus Security scan status (when expand=true)
     */
    public function __construct(
        public string $path,
        public string $type,
        public string $oid,
        public int $size,
        public ?BlobLfsInfo $lfs = null,
        public ?LastCommitInfo $lastCommit = null,
        public ?SecurityStatus $securityStatus = null,
    ) {}

    /**
     * Check if this is a file.
     */
    public function isFile(): bool
    {
        return 'file' === $this->type;
    }

    /**
     * Check if this is a directory.
     */
    public function isDirectory(): bool
    {
        return 'directory' === $this->type;
    }

    /**
     * Check if this file is stored in LFS.
     */
    public function isLfs(): bool
    {
        return null !== $this->lfs;
    }

    /**
     * Get the filename (basename).
     */
    public function filename(): string
    {
        return basename($this->path);
    }

    /**
     * Get the short OID (first 7 characters).
     */
    public function shortOid(): string
    {
        return substr($this->oid, 0, 7);
    }

    /**
     * Get human-readable file size.
     */
    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < \count($units) - 1) {
            $size /= 1024;
            ++$unit;
        }

        return round($size, 2).' '.$units[$unit];
    }

    /**
     * Get the actual file size (LFS size if LFS, otherwise regular size).
     */
    public function actualSize(): int
    {
        return $this->lfs->size ?? $this->size;
    }

    /**
     * Get the directory path (parent directory).
     */
    public function directory(): string
    {
        return \dirname($this->path);
    }

    /**
     * Get the file extension.
     */
    public function extension(): string
    {
        return pathinfo($this->path, \PATHINFO_EXTENSION);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            path: $data['path'] ?? '',
            type: $data['type'] ?? 'file',
            oid: $data['oid'] ?? '',
            size: $data['size'] ?? 0,
            lfs: isset($data['lfs']) ? BlobLfsInfo::fromArray($data['lfs']) : null,
            lastCommit: isset($data['lastCommit']) ? LastCommitInfo::fromArray($data['lastCommit']) : null,
            securityStatus: isset($data['securityFileStatus']) ? SecurityStatus::fromArray($data['securityFileStatus']) : null,
        );
    }
}
