<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;

final readonly class CollectionItemModel extends CollectionItem
{
    public function __construct(
        string $id,
        int $position,
        ?array $note,
        public string $repoId, // 'id' from API
        public ?string $pipelineTag,
        public bool $private,
        public int $downloads,
        public int $likes,
        public string $lastModified,
        public ?Author $author,
        public ?int $numParameters = null,
    ) {
        parent::__construct($id, $position, CollectionItemType::Model, $note);
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
            pipelineTag: $data['pipeline_tag'] ?? null,
            private: $data['private'] ?? false,
            downloads: $data['downloads'] ?? 0,
            likes: $data['likes'] ?? 0,
            lastModified: $data['lastModified'] ?? '',
            author: $author,
            numParameters: $data['numParameters'] ?? null,
        );
    }
}
