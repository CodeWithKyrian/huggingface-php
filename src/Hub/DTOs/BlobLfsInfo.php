<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents LFS pointer information for a file.
 */
final readonly class BlobLfsInfo extends Resource
{
    /**
     * @param string $oid         the LFS object ID (SHA256 hash of the file content)
     * @param int    $size        the actual file size in bytes
     * @param int    $pointerSize the size of the pointer file in bytes
     */
    public function __construct(
        public string $oid,
        public int $size,
        public int $pointerSize,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            oid: $data['oid'] ?? '',
            size: $data['size'] ?? 0,
            pointerSize: $data['pointerSize'] ?? 0,
        );
    }
}
