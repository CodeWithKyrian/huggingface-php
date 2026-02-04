<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents a file sibling in a Hugging Face repository.
 *
 * Contains basic information about a file inside a repo on the Hub.
 */
final readonly class RepoSibling extends Resource
{
    public function __construct(
        public string $rfilename,
        public ?int $size = null,
        public ?string $blobId = null,
        public ?BlobLfsInfo $lfs = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            rfilename: $data['rfilename'] ?? $data['rfilename'] ?? '',
            size: isset($data['size']) ? (int) $data['size'] : null,
            blobId: $data['blob_id'] ?? $data['blobId'] ?? null,
            lfs: isset($data['lfs']) ? BlobLfsInfo::fromArray($data['lfs']) : null,
        );
    }
}
