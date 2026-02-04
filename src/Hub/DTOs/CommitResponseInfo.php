<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Minimal commit info returned in the commit response.
 */
final readonly class CommitResponseInfo extends Resource
{
    public function __construct(
        public string $oid,
        public string $url,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            oid: $data['oid'] ?? '',
            url: $data['url'] ?? '',
        );
    }
}
