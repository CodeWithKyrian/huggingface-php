<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Support\Utils;

/**
 * Represents download information for a file without actually downloading it.
 */
final readonly class FileDownloadInfo extends Resource
{
    /**
     * @param int    $size The file size in bytes
     * @param string $etag The ETag header value
     * @param string $url  The URL to download the file from
     */
    public function __construct(
        public int $size,
        public string $etag,
        public string $url,
    ) {}

    /**
     * Get human-readable file size.
     */
    public function humanSize(): string
    {
        return Utils::formatBytes($this->size);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            size: $data['size'] ?? 0,
            etag: $data['etag'] ?? '',
            url: $data['url'] ?? '',
        );
    }
}
