<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Managers;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\DTOs\CollectionInfo;
use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;

/**
 * Manages a specific collection on the Hub.
 */
final class CollectionManager
{
    public function __construct(
        private readonly string $hubUrl,
        private readonly HttpConnector $http,
        private readonly string $slug,
    ) {}

    /**
     * Get detailed information about the collection.
     */
    public function info(): CollectionInfo
    {
        $url = "{$this->hubUrl}/api/collections/{$this->slug}";
        $response = $this->http->get($url);

        return CollectionInfo::fromArray($response->json());
    }

    /**
     * Add an item to the collection.
     *
     * @param string                    $itemId The repository ID (e.g. "username/repo") or paper ID.
     * @param CollectionItemType|string $type   the type of item (model, dataset, space, paper)
     * @param null|string               $note   optional note
     */
    public function addItem(string $itemId, CollectionItemType|string $type, ?string $note = null): void
    {
        $url = "{$this->hubUrl}/api/collections/{$this->slug}/items";

        $typeStr = $type instanceof CollectionItemType ? $type->value : $type;

        $payload = [
            'item' => [
                'type' => $typeStr,
                'id' => $itemId,
            ],
        ];

        if ($note) {
            $payload['note'] = $note;
        }

        $this->http->post($url, $payload);
    }

    /**
     * Remove an item from the collection.
     *
     * @param string $itemId the item object ID (not the repo ID)
     */
    public function deleteItem(string $itemId): void
    {
        $url = "{$this->hubUrl}/api/collections/{$this->slug}/items/{$itemId}";
        $this->http->delete($url);
    }

    /**
     * Delete the collection.
     */
    public function delete(): void
    {
        $url = "{$this->hubUrl}/api/collections/{$this->slug}";
        $this->http->delete($url);
    }
}
