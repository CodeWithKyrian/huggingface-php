<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Collection of git references (branches, tags, converts) for a repository.
 */
class GitRefs
{
    /**
     * @param GitRef[] $branches List of branch references
     * @param GitRef[] $tags     List of tag references
     * @param GitRef[] $converts List of convert references (for safetensors conversions, etc.)
     */
    public function __construct(
        public readonly array $branches = [],
        public readonly array $tags = [],
        public readonly array $converts = [],
    ) {}

    /**
     * Get all refs combined (branches + tags + converts).
     *
     * @return GitRef[]
     */
    public function all(): array
    {
        return array_merge($this->branches, $this->tags, $this->converts);
    }

    /**
     * Find a branch by name.
     */
    public function findBranch(string $name): ?GitRef
    {
        foreach ($this->branches as $branch) {
            if ($branch->name === $name) {
                return $branch;
            }
        }

        return null;
    }

    /**
     * Find a tag by name.
     */
    public function findTag(string $name): ?GitRef
    {
        foreach ($this->tags as $tag) {
            if ($tag->name === $name) {
                return $tag;
            }
        }

        return null;
    }

    /**
     * Check if a branch exists.
     */
    public function hasBranch(string $name): bool
    {
        return null !== $this->findBranch($name);
    }

    /**
     * Check if a tag exists.
     */
    public function hasTag(string $name): bool
    {
        return null !== $this->findTag($name);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $mapRefs = static fn (array $items) => array_map(
            static fn (array $item) => GitRef::fromArray($item),
            $items
        );

        return new self(
            branches: $mapRefs($data['branches'] ?? []),
            tags: $mapRefs($data['tags'] ?? []),
            converts: $mapRefs($data['converts'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $mapToArray = static fn (array $refs) => array_map(
            static fn (GitRef $ref) => $ref->toArray(),
            $refs
        );

        return [
            'branches' => $mapToArray($this->branches),
            'tags' => $mapToArray($this->tags),
            'converts' => $mapToArray($this->converts),
        ];
    }
}
