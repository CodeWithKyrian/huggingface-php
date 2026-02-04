<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;

final readonly class CollectionItemPaper extends CollectionItem
{
    public function __construct(
        string $id,
        int $position,
        ?array $note,
        public string $paperId, // 'id'
        public string $title,
        public int $upvotes,
    ) {
        parent::__construct($id, $position, CollectionItemType::Paper, $note);
    }

    public function itemId(): string
    {
        return $this->paperId;
    }

    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['_id'],
            position: $data['position'] ?? 0,
            note: $data['note'] ?? null,
            paperId: $data['id'],
            title: $data['title'],
            upvotes: $data['upvotes'] ?? 0,
        );
    }
}
