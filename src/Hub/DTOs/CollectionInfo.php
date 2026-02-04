<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;

/**
 * Information about a collection on the Hub.
 */
final class CollectionInfo
{
    /**
     * @param CollectionItem[] $items
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly bool $private,
        public readonly Author $owner,
        public readonly int $upvotes,
        public readonly string $lastModified,
        public readonly string $url,
        public readonly array $items = [],
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $items = isset($data['items'])
            ? array_map(static function (array $item) {
                $target = $item['item'] ?? $item;
                $typeStr = $target['type'] ?? $target['repoType'] ?? 'model';
                $type = CollectionItemType::tryFrom($typeStr) ?? CollectionItemType::Model;

                return match ($type) {
                    CollectionItemType::Model => CollectionItemModel::fromArray($item),
                    CollectionItemType::Dataset => CollectionItemDataset::fromArray($item),
                    CollectionItemType::Space => CollectionItemSpace::fromArray($item),
                    CollectionItemType::Paper => CollectionItemPaper::fromArray($item),
                    CollectionItemType::Collection => CollectionItemCollection::fromArray($item),
                };
            }, $data['items'])
            : [];

        return new self(
            slug: $data['slug'],
            id: $data['_id'] ?? '',
            title: $data['title'],
            description: $data['description'] ?? null,
            private: $data['private'] ?? false,
            owner: Author::fromArray($data['owner']),
            upvotes: $data['upvotes'] ?? 0,
            lastModified: $data['lastModified'] ?? '',
            url: "https://huggingface.co/collections/{$data['slug']}",
            items: $items,
        );
    }
}
