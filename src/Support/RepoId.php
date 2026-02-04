<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Support;

use Codewithkyrian\HuggingFace\Hub\Exceptions\InvalidRepoIdException;

/**
 * Value object representing a Hugging Face repository identifier.
 *
 * Supports formats:
 * - "owner/repo" (e.g., "meta-llama/Llama-2-7b")
 * - "repo" (assumes current user as owner)
 */
final readonly class RepoId
{
    public function __construct(
        public string $owner,
        public string $name,
    ) {}

    public function __toString(): string
    {
        return $this->isComplete()
            ? "{$this->owner}/{$this->name}"
            : $this->name;
    }

    /**
     * Parse a repository ID string into a RepoId object.
     *
     * @throws InvalidRepoIdException
     */
    public static function parse(string $repoId): self
    {
        $repoId = trim($repoId);

        if ('' === $repoId) {
            throw new InvalidRepoIdException('Repository ID cannot be empty');
        }

        $parts = explode('/', $repoId, 2);

        if (1 === \count($parts)) {
            // Only repo name provided, owner will need to be resolved later
            return new self('', $parts[0]);
        }

        [$owner, $name] = $parts;

        if ('' === $owner || '' === $name) {
            throw new InvalidRepoIdException(
                "Invalid repository ID format: '{$repoId}'. Expected 'owner/repo' or 'repo'"
            );
        }

        return new self($owner, $name);
    }

    /**
     * Check if this is a valid complete repo ID (has owner).
     */
    public function isComplete(): bool
    {
        return '' !== $this->owner;
    }

    /**
     * URL-encode the repository ID for use in API paths.
     */
    public function toUrlPath(): string
    {
        if ($this->isComplete()) {
            return rawurlencode($this->owner).'/'.rawurlencode($this->name);
        }

        return rawurlencode($this->name);
    }
}
