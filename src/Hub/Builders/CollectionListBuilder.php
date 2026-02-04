<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\DTOs\CollectionInfo;
use Codewithkyrian\HuggingFace\Hub\Enums\CollectionSortField;
use Codewithkyrian\HuggingFace\Support\Utils;

/**
 * Fluent builder for listing collections on the Hub.
 */
final class CollectionListBuilder
{
    private ?string $search = null;

    /** @var string[] */
    private array $owners = [];

    /** @var string[] */
    private array $items = [];
    private CollectionSortField $sort = CollectionSortField::Trending;
    private ?int $limit = null;
    private int $offset = 0;

    public function __construct(
        private readonly string $hubUrl,
        private readonly HttpConnector $http,
    ) {}

    /**
     * Search by query string (titles & descriptions).
     */
    public function search(string $query): self
    {
        $this->search = $query;

        return $this;
    }

    /**
     * Filter collections created by specific owner (user or organization).
     */
    public function owner(string $owner): self
    {
        $this->owners[] = $owner;

        return $this;
    }

    /**
     * Filter collections containing specific item.
     * Value must be the item_type and item_id concatenated.
     * Example: "models/teknium/OpenHermes-2.5-Mistral-7B", "papers/2311.12983".
     */
    public function item(string $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Sort the returned collections.
     */
    public function sort(CollectionSortField $field): self
    {
        $this->sort = $field;

        return $this;
    }

    /**
     * Limit the number of collections returned.
     * Set to null for infinite pagination.
     */
    public function limit(?int $limit): self
    {
        $clone = clone $this;
        $clone->limit = null !== $limit ? max(1, $limit) : null;

        return $clone;
    }

    /**
     * Set the offset for pagination.
     */
    public function offset(int $offset): self
    {
        $clone = clone $this;
        $clone->offset = max(0, $offset);

        return $clone;
    }

    /**
     * Execute the query and get results as a generator.
     *
     * @return \Generator<CollectionInfo>
     */
    public function get(): \Generator
    {
        $baseUrl = "{$this->hubUrl}/api/collections";
        $totalToFetch = $this->limit ?? \PHP_INT_MAX;
        $fetchedCount = 0;

        $query = $this->buildQuery();
        $query['offset'] = $this->offset;
        $query['limit'] = min($totalToFetch, 500);

        $url = Utils::buildUrl($baseUrl, $query);

        while ($url) {
            $response = $this->http->get($url);
            $items = $response->json();

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                yield CollectionInfo::fromArray($item);
                if (++$fetchedCount >= $totalToFetch) {
                    return;
                }
            }

            $linkHeader = $response->header('Link');
            $url = null;

            if ($linkHeader) {
                $links = Utils::parseLinkHeader($linkHeader);
                $url = $links['next'] ?? null;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuery(): array
    {
        $query = [
            'sort' => $this->sort->value,
        ];

        if ($this->search) {
            $query['q'] = $this->search;
        }

        if (!empty($this->owners)) {
            $query['owner'] = $this->owners;
        }

        if (!empty($this->items)) {
            $query['item'] = $this->items;
        }

        return $query;
    }
}
