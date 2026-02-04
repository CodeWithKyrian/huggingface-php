<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents the security scan status of a file.
 */
final readonly class SecurityStatus extends Resource
{
    public function __construct(
        /**
         * The security status (e.g., 'safe', 'unsafe', 'pending').
         */
        public string $status,
    ) {}

    /**
     * Check if the file is marked as safe.
     */
    public function isSafe(): bool
    {
        return 'safe' === $this->status;
    }

    /**
     * Check if the file is marked as unsafe.
     */
    public function isUnsafe(): bool
    {
        return 'unsafe' === $this->status;
    }

    /**
     * Check if the security scan is pending.
     */
    public function isPending(): bool
    {
        return 'pending' === $this->status;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            status: $data['status'] ?? 'unknown',
        );
    }
}
