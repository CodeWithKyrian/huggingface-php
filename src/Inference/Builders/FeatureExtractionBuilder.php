<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\Enums\TruncationDirection;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for feature extraction (embeddings) requests.
 */
final class FeatureExtractionBuilder
{
    private bool $normalize = false;
    private bool $truncate = false;

    private ?string $promptName = null;
    private ?TruncationDirection $truncationDirection = null;

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
     * Normalize embeddings to unit length.
     *
     * Useful for cosine similarity comparisons.
     */
    public function normalize(bool $normalize = true): self
    {
        $this->normalize = $normalize;

        return $this;
    }

    /**
     * Truncate inputs that exceed model's max length.
     */
    public function truncate(bool $truncate = true): self
    {
        $this->truncate = $truncate;

        return $this;
    }

    /**
     * Set the name of the prompt to apply to the inputs.
     *
     * Must be a key in the `sentence-transformers` configuration `prompts` dictionary.
     */
    public function promptName(string $promptName): self
    {
        $this->promptName = $promptName;

        return $this;
    }

    /**
     * Set the direction of truncation.
     */
    public function truncationDirection(TruncationDirection $direction): self
    {
        $this->truncationDirection = $direction;

        return $this;
    }

    /**
     * Execute the request and return embeddings.
     *
     * @param array<string>|string $inputs Text(s) to embed
     *
     * @return array<array<float>>|array<float> Embedding vector(s)
     */
    public function execute(array|string $inputs): array
    {
        $payload = $this->buildPayload($inputs);
        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        return $this->helper->getResponse($response->json());
    }

    /**
     * Build the request payload.
     *
     * @param array<string>|string $inputs
     *
     * @return array<string, mixed>
     */
    private function buildPayload(array|string $inputs): array
    {
        $payload = ['inputs' => $inputs];

        if ($this->normalize) {
            $payload['normalize'] = true;
        }

        if ($this->truncate) {
            $payload['truncate'] = true;
        }

        if (null !== $this->promptName) {
            $payload['prompt_name'] = $this->promptName;
        }

        if (null !== $this->truncationDirection) {
            $payload['truncation_direction'] = $this->truncationDirection->value;
        }

        return $payload;
    }
}
