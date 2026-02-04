<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;

final readonly class CollectionItemSpace extends CollectionItem
{
    public function __construct(
        string $id,
        int $position,
        ?array $note,
        public string $repoId,
        public string $title,
        public bool $private,
        public int $likes,
        public string $lastModified,
        public ?string $sdk,
        public ?string $runtimeStage,
        public ?Author $author,
    ) {
        parent::__construct($id, $position, CollectionItemType::Space, $note);
    }

    public function itemId(): string
    {
        return $this->repoId;
    }

    public static function fromArray(array $data): static
    {
        $author = isset($data['authorData']) ? Author::fromArray($data['authorData']) : null;

        return new self(
            id: $data['_id'],
            position: $data['position'] ?? 0,
            note: $data['note'] ?? null,
            repoId: $data['id'],
            title: $data['title'] ?? '',
            private: $data['private'] ?? false,
            likes: $data['likes'] ?? 0,
            lastModified: $data['lastModified'] ?? '',
            sdk: $data['sdk'] ?? null,
            runtimeStage: $data['runtime']['stage'] ?? null,
            author: $author,
        );
    }
}
