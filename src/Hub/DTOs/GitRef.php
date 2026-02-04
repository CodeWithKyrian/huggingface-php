<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents a git reference (branch or tag) on a Hugging Face repository.
 */
class GitRef
{
    public function __construct(
        /**
         * Name of the reference (e.g., "main", "v1.0").
         */
        public readonly string $name,

        /**
         * Full git ref path (e.g., "refs/heads/main" or "refs/tags/v1.0").
         */
        public readonly string $ref,

        /**
         * OID of the target commit for this ref.
         */
        public readonly string $targetCommit,
    ) {}

    /**
     * Check if this is a branch reference.
     */
    public function isBranch(): bool
    {
        return str_starts_with($this->ref, 'refs/heads/');
    }

    /**
     * Check if this is a tag reference.
     */
    public function isTag(): bool
    {
        return str_starts_with($this->ref, 'refs/tags/');
    }

    /**
     * Check if this is a convert reference.
     */
    public function isConvert(): bool
    {
        return str_starts_with($this->ref, 'refs/convert/');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            ref: $data['ref'],
            targetCommit: $data['targetCommit'] ?? $data['target_commit'] ?? '',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'ref' => $this->ref,
            'targetCommit' => $this->targetCommit,
        ];
    }
}
