<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;

/**
 * Base class for an item within a collection.
 */
abstract readonly class CollectionItem extends Resource
{
    /**
     * @param string                                 $id       The item's unique ID within the collection
     * @param int                                    $position The position of the item in the collection
     * @param CollectionItemType                     $type     The type of the item
     * @param null|array{text: string, html: string} $note     The note associated with the item
     */
    public function __construct(
        public string $id,
        public int $position,
        public CollectionItemType $type,
        public ?array $note = null,
    ) {}

    /**
     * Get the human-readable ID of the item (e.g. repo ID, paper ID, or slug).
     */
    abstract public function itemId(): string;
}
