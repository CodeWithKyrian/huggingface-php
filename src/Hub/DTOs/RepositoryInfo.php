<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;

/**
 * Represents a Hugging Face repository (model, dataset, or space).
 */
abstract readonly class RepositoryInfo extends Resource
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $author,
        public RepoType $type,
        public bool $private,
        public false|string $gated,
        public bool $disabled,
        public ?string $sha,
        public ?\DateTimeImmutable $lastModified,
        public ?\DateTimeImmutable $createdAt,
        public int $downloads,
        public int $likes,
        /** @var string[] */
        public array $tags,
        /** @var RepoSibling[] */
        public array $siblings,
    ) {}

    /**
     * Get the full repo path (owner/name).
     */
    public function fullName(): string
    {
        return $this->author ? "{$this->author}/{$this->name}" : $this->name;
    }

    /**
     * Get the Hub URL for this repository.
     */
    public function url(string $endpoint = 'https://huggingface.co'): string
    {
        $prefix = match ($this->type) {
            RepoType::Model => '',
            RepoType::Dataset => 'datasets/',
            RepoType::Space => 'spaces/',
        };

        return "{$endpoint}/{$prefix}{$this->id}";
    }
}
