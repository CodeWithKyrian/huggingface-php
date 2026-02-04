<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;

final readonly class CollectionItemCollection extends CollectionItem
{
    public function __construct(
        string $id,
        int $position,
        ?array $note,
        public string $slug,
        public string $title,
        public string $description,
        public int $upvotes,
        public int $itemCount,
        public ?Author $owner,
    ) {
        parent::__construct($id, $position, CollectionItemType::Collection, $note);
    }

    public function itemId(): string
    {
        return $this->slug;
    }

    public static function fromArray(array $data): static
    {
        $owner = isset($data['owner']) ? Author::fromArray($data['owner']) : null;

        return new self(
            id: $data['_id'],
            position: $data['position'] ?? 0,
            note: $data['note'] ?? null,
            slug: $data['slug'],
            title: $data['title'],
            description: $data['description'] ?? '',
            upvotes: $data['upvotes'] ?? 0,
            itemCount: $data['numberItems'] ?? 0,
            owner: $owner,
        );
    }
}
