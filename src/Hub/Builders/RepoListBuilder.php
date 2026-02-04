<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\DTOs\DatasetInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\ModelInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\SpaceInfo;
use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Hub\Enums\SortField;
use Codewithkyrian\HuggingFace\Support\Utils;

/**
 * Fluent builder for listing models, datasets and spaces on the Hub.
 *
 * @template T of ModelInfo|DatasetInfo|SpaceInfo
 */
final class RepoListBuilder
{
    private ?string $search = null;
    private ?string $author = null;
    private ?string $task = null;
    private ?string $library = null;
    private ?string $language = null;

    /** @var string[] */
    private array $tags = [];
    private SortField $sort = SortField::Downloads;
    private string $direction = 'desc';
    private ?int $limit = null;
    private bool $full = false;
    private bool $config = false;

    public function __construct(
        private readonly string $hubUrl,
        private readonly HttpConnector $http,
        private readonly RepoType $repoType,
    ) {}

    /**
     * Filter by search query string.
     *
     * @return self<T>
     */
    public function search(string $query): self
    {
        $clone = clone $this;
        $clone->search = $query;

        return $clone;
    }

    /**
     * Filter by author (organization or user).
     *
     * @return self<T>
     */
    public function author(string $author): self
    {
        $clone = clone $this;
        $clone->author = $author;

        return $clone;
    }

    /**
     * Filter by task/pipeline (e.g., 'text-generation', 'text-classification').
     *
     * @return self<T>
     */
    public function task(string $task): self
    {
        $clone = clone $this;
        $clone->task = $task;

        return $clone;
    }

    /**
     * Filter by library (e.g., 'transformers', 'pytorch').
     *
     * @return self<T>
     */
    public function library(string $library): self
    {
        $clone = clone $this;
        $clone->library = $library;

        return $clone;
    }

    /**
     * Filter by language (ISO code, e.g., 'en', 'fr').
     *
     * @return self<T>
     */
    public function language(string $language): self
    {
        $clone = clone $this;
        $clone->language = $language;

        return $clone;
    }

    /**
     * Filter by tags.
     *
     * @return self<T>
     */
    public function tag(string $tag): self
    {
        $clone = clone $this;
        $clone->tags = [...$this->tags, $tag];

        return $clone;
    }

    /**
     * Filter by multiple tags.
     *
     * @param string[] $tags
     *
     * @return self<T>
     */
    public function tags(array $tags): self
    {
        $clone = clone $this;
        $clone->tags = [...$this->tags, ...$tags];

        return $clone;
    }

    /**
     * Custom filter string.
     *
     * @return self<T>
     */
    public function filter(string $filter): self
    {
        return $this->tag($filter);
    }

    /**
     * Sort results by field.
     *
     * @return self<T>
     */
    public function sort(SortField $field): self
    {
        $clone = clone $this;
        $clone->sort = $field;

        return $clone;
    }

    /**
     * Sort in ascending order.
     *
     * @return self<T>
     */
    public function ascending(): self
    {
        $clone = clone $this;
        $clone->direction = 'asc';

        return $clone;
    }

    /**
     * Sort in descending order (default).
     *
     * @return self<T>
     */
    public function descending(): self
    {
        $clone = clone $this;
        $clone->direction = 'desc';

        return $clone;
    }

    /**
     * Limit the number of repositories returned.
     * Set to null for infinite pagination.
     *
     * @return self<T>
     */
    public function limit(?int $limit): self
    {
        $clone = clone $this;
        $clone->limit = null !== $limit ? max(1, $limit) : null;

        return $clone;
    }

    /**
     * Include full model/dataset info.
     *
     * @return self<T>
     */
    public function full(bool $full = true): self
    {
        $clone = clone $this;
        $clone->full = $full;

        return $clone;
    }

    /**
     * Execute the query and get results as a generator.
     *
     * @return \Generator<T>
     */
    public function get(): \Generator
    {
        $endpoint = $this->repoType->apiPath();
        $baseUrl = "{$this->hubUrl}/api/{$endpoint}";

        $totalToFetch = $this->limit ?? \PHP_INT_MAX;
        $fetchedCount = 0;

        $query = $this->buildQuery();
        $query['limit'] = min($totalToFetch, 500);

        $url = Utils::buildUrl($baseUrl, $query);

        while ($url) {
            $response = $this->http->get($url);
            $items = $response->json();

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                yield match ($this->repoType) {
                    RepoType::Model => ModelInfo::fromArray($item),
                    RepoType::Dataset => DatasetInfo::fromArray($item),
                    RepoType::Space => SpaceInfo::fromArray($item),
                };

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
     * Get the first result only.
     *
     * @return null|T
     */
    public function first(): DatasetInfo|ModelInfo|SpaceInfo|null
    {
        foreach ($this->limit(1)->get() as $item) {
            return $item;
        }

        return null;
    }

    /**
     * Build the query parameters.
     *
     * @return array<string, mixed>
     */
    private function buildQuery(): array
    {
        $query = [
            'sort' => $this->sort->value,
            'direction' => 'asc' === $this->direction ? '1' : '-1',
        ];

        if (null !== $this->search) {
            $query['search'] = $this->search;
        }

        if (null !== $this->author) {
            $query['author'] = $this->author;
        }

        if (null !== $this->task) {
            $query['pipeline_tag'] = $this->task;
        }

        if (null !== $this->library) {
            $query['library'] = $this->library;
        }

        if (null !== $this->language) {
            $query['language'] = $this->language;
        }

        if (!empty($this->tags)) {
            $query['filter'] = $this->tags;
        }

        if ($this->full) {
            $query['full'] = 'true';
        }

        if ($this->config) {
            $query['config'] = 'true';
        }

        return $query;
    }
}
