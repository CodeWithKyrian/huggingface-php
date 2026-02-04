<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\TokenClassificationOutput;
use Codewithkyrian\HuggingFace\Inference\Enums\AggregationStrategy;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for token classification (NER) requests.
 */
final class TokenClassificationBuilder
{
    private ?AggregationStrategy $aggregationStrategy = null;

    /** @var null|string[] */
    private ?array $ignoreLabels = null;
    private ?int $stride = null;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly HttpConnector $http,
        private readonly ProviderHelperInterface $helper,
        private readonly string $model,
        private readonly string $url,
        private readonly array $headers,
    ) {}

    /**
     * Set the strategy used to fuse tokens based on model predictions.
     */
    public function aggregationStrategy(AggregationStrategy $strategy): self
    {
        $this->aggregationStrategy = $strategy;

        return $this;
    }

    /**
     * Add a list of labels to ignore.
     *
     * @param string[] $labels
     */
    public function ignoreLabels(array $labels): self
    {
        $this->ignoreLabels = array_merge($this->ignoreLabels ?? [], $labels);

        return $this;
    }

    /**
     * Add a single label to ignore.
     */
    public function ignoreLabel(string $label): self
    {
        $this->ignoreLabels[] = $label;

        return $this;
    }

    /**
     * Set the number of overlapping tokens between chunks when splitting the input text.
     */
    public function stride(int $stride): self
    {
        $this->stride = $stride;

        return $this;
    }

    /**
     * Execute the request and return classification results.
     *
     * @param string $inputs Text to classify
     *
     * @return array<TokenClassificationOutput>
     */
    public function execute(string $inputs): array
    {
        $parameters = array_filter([
            'aggregation_strategy' => $this->aggregationStrategy?->value,
            'ignore_labels' => $this->ignoreLabels,
            'stride' => $this->stride,
        ], static fn ($v) => null !== $v);

        $payload = ['inputs' => $inputs];

        if (!empty($parameters)) {
            $payload['parameters'] = $parameters;
        }

        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        $data = $this->helper->getResponse($response->json());

        return array_map(
            static fn (array $item) => TokenClassificationOutput::fromArray($item),
            $data
        );
    }
}
