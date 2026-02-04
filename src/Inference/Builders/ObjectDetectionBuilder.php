<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\ObjectDetectionOutput;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for object detection requests.
 */
final class ObjectDetectionBuilder
{
    private ?float $threshold = null;

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
     * Set the probability threshold necessary to make a prediction.
     */
    public function threshold(float $threshold): self
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Execute the request and return detection results.
     *
     * @param string $inputs URL or base64-encoded image
     *
     * @return array<ObjectDetectionOutput>
     */
    public function execute(string $inputs): array
    {
        $parameters = array_filter([
            'threshold' => $this->threshold,
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
            static fn (array $item) => ObjectDetectionOutput::fromArray($item),
            $data
        );
    }
}
