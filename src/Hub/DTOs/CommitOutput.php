<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents the output from a commit operation.
 */
final readonly class CommitOutput extends Resource
{
    public function __construct(
        public ?string $pullRequestUrl,
        public CommitResponseInfo $commit,
        public string $hookOutput,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            pullRequestUrl: $data['pullRequestUrl'] ?? null,
            commit: CommitResponseInfo::fromArray([
                'oid' => $data['commitOid'] ?? $data['commit']['oid'] ?? '',
                'url' => $data['commitUrl'] ?? $data['commit']['url'] ?? '',
            ]),
            hookOutput: $data['hookOutput'] ?? '',
        );
    }
}
