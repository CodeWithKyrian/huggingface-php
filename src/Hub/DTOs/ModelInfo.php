<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;

/**
 * Represents a model on the Hugging Face Hub.
 */
final readonly class ModelInfo extends RepositoryInfo
{
    /**
     * @param RepoSibling[]      $siblings
     * @param string[]           $tags
     * @param string[]           $languages
     * @param null|ModelCardData $cardData
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
        public ?string $pipelineTag,
        public ?string $library,
        public array $languages,
        public mixed $cardData,
    ) {
        parent::__construct(
            id: $id,
            name: $name,
            author: $author,
            type: RepoType::Model,
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
        $id = $data['modelId'] ?? $data['id'] ?? $data['_id'] ?? '';

        // Parse author from ID if not provided directly
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
            downloads: self::parseInt($data['downloads'] ?? 0),
            likes: self::parseInt($data['likes'] ?? 0),
            tags: $data['tags'] ?? [],
            siblings: array_map(
                static fn (array $file): mixed => RepoSibling::fromArray($file),
                $data['siblings'] ?? []
            ),
            pipelineTag: $data['pipeline_tag'] ?? $data['pipelineTag'] ?? null,
            library: $data['library_name'] ?? $data['library'] ?? null,
            languages: $data['languages'] ?? [],
            cardData: isset($data['cardData']) ? ModelCardData::fromArray($data['cardData']) : null,
        );
    }

    /**
     * Get the Hub URL for this model.
     */
    public function url(string $endpoint = 'https://huggingface.co'): string
    {
        return "{$endpoint}/{$this->id}";
    }

    /**
     * Check if the model has a specific tag.
     */
    public function hasTag(string $tag): bool
    {
        return \in_array($tag, $this->tags, true);
    }
}
