<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Hub\Enums\SpaceSdk;
use Codewithkyrian\HuggingFace\Hub\Enums\SpaceStage;

/**
 * Represents a Space on the Hugging Face Hub.
 */
final readonly class SpaceInfo extends RepositoryInfo
{
    /**
     * @param RepoSibling[] $siblings
     * @param string[]      $tags
     */
    public function __construct(
        string $id,
        string $name,
        ?string $author,
        bool $private,
        false|string $gated,
        bool $disabled,
        ?string $sha,
        ?\DateTimeImmutable $lastModified,
        ?\DateTimeImmutable $createdAt,
        int $downloads,
        int $likes,
        array $tags,
        array $siblings,
        public ?SpaceSdk $sdk,
        public ?SpaceRuntime $runtime,
        public mixed $cardData,
    ) {
        parent::__construct(
            id: $id,
            name: $name,
            author: $author,
            type: RepoType::Space,
            private: $private,
            gated: $gated,
            disabled: $disabled,
            sha: $sha,
            lastModified: $lastModified,
            createdAt: $createdAt,
            downloads: $downloads,
            likes: $likes,
            tags: $tags,
            siblings: $siblings,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $id = $data['id'] ?? $data['_id'] ?? '';

        $author = $data['author'] ?? null;
        if (null === $author && str_contains($id, '/')) {
            $author = explode('/', $id, 2)[0];
        }

        return new self(
            id: $id,
            name: str_contains($id, '/') ? explode('/', $id, 2)[1] : $id,
            author: $author,
            private: self::parseBool($data['private'] ?? false),
            gated: isset($data['gated']) ? $data['gated'] : false,
            disabled: self::parseBool($data['disabled'] ?? false),
            sha: $data['sha'] ?? null,
            lastModified: self::parseDateTime($data['lastModified'] ?? null),
            createdAt: self::parseDateTime($data['createdAt'] ?? null),
            downloads: 0,
            likes: self::parseInt($data['likes'] ?? 0),
            tags: $data['tags'] ?? [],
            siblings: array_map(
                static fn (array $file) => RepoSibling::fromArray($file),
                $data['siblings'] ?? []
            ),
            sdk: isset($data['sdk']) ? SpaceSdk::tryFrom($data['sdk']) : null,
            runtime: isset($data['runtime']) ? SpaceRuntime::fromArray($data['runtime']) : null,
            cardData: $data['cardData'] ?? null,
        );
    }

    /**
     * Get the Hub URL for this space.
     */
    public function url(string $endpoint = 'https://huggingface.co'): string
    {
        return "{$endpoint}/spaces/{$this->id}";
    }

    /**
     * Check if the space has a specific tag.
     */
    public function hasTag(string $tag): bool
    {
        return \in_array($tag, $this->tags, true);
    }

    /**
     * Check if the space is running.
     */
    public function isRunning(): bool
    {
        return SpaceStage::Running === $this->runtime?->stage;
    }

    /**
     * Check if the space is sleeping.
     */
    public function isSleeping(): bool
    {
        return SpaceStage::Sleeping === $this->runtime?->stage;
    }
}
