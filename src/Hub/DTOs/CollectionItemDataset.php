<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;

final readonly class CollectionItemDataset extends CollectionItem
{
    public function __construct(
        string $id,
        int $position,
        ?array $note,
        public string $repoId,
        public bool $private,
        public int $downloads,
        public int $likes,
        public string $lastModified,
        public ?string $authorName,
    ) {
        parent::__construct($id, $position, CollectionItemType::Dataset, $note);
    }

    public function itemId(): string
    {
        return $this->repoId;
    }

    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['_id'],
            position: $data['position'] ?? 0,
            note: $data['note'] ?? null,
            repoId: $data['id'],
            private: $data['private'] ?? false,
            downloads: $data['downloads'] ?? 0,
            likes: $data['likes'] ?? 0,
            lastModified: $data['lastModified'] ?? '',
            authorName: $data['author'] ?? null,
        );
    }
}
