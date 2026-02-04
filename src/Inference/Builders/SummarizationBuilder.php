<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\SummarizationOutput;
use Codewithkyrian\HuggingFace\Inference\Enums\TruncationStrategy;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for summarization requests.
 */
final class SummarizationBuilder
{
    private ?int $maxLength = null;
    private ?int $minLength = null;
    private ?float $temperature = null;
    private ?bool $doSample = null;

    private ?bool $cleanUpTokenizationSpaces = null;
    private ?TruncationStrategy $truncation = null;

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
     * Set maximum length of the summary.
     */
    public function maxLength(int $maxLength): self
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * Set minimum length of the summary.
     */
    public function minLength(int $minLength): self
    {
        $this->minLength = $minLength;

        return $this;
    }

    /**
     * Set the sampling temperature.
     */
    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Enable sampling (vs beam search).
     */
    public function doSample(bool $doSample = true): self
    {
        $this->doSample = $doSample;

        return $this;
    }

    /**
     * Whether to clean up the potential extra spaces in the text output.
     */
    public function cleanUpTokenizationSpaces(bool $cleanUpTokenizationSpaces): self
    {
        $this->cleanUpTokenizationSpaces = $cleanUpTokenizationSpaces;

        return $this;
    }

    /**
     * The truncation strategy to use.
     */
    public function truncation(TruncationStrategy $truncation): self
    {
        $this->truncation = $truncation;

        return $this;
    }

    /**
     * Execute the request and return the summary.
     *
     * @param string $inputs Text to summarize
     */
    public function execute(string $inputs): SummarizationOutput
    {
        $payload = $this->buildPayload($inputs);
        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        $data = $this->helper->getResponse($response->json());

        return SummarizationOutput::fromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $inputs): array
    {
        $payload = ['inputs' => $inputs];
        $parameters = [];

        if (null !== $this->maxLength) {
            $parameters['max_length'] = $this->maxLength;
        }

        if (null !== $this->minLength) {
            $parameters['min_length'] = $this->minLength;
        }

        if (null !== $this->temperature) {
            $parameters['temperature'] = $this->temperature;
        }

        if (null !== $this->doSample) {
            $parameters['do_sample'] = $this->doSample;
        }

        if (null !== $this->cleanUpTokenizationSpaces) {
            $parameters['clean_up_tokenization_spaces'] = $this->cleanUpTokenizationSpaces;
        }

        if (null !== $this->truncation) {
            $parameters['truncation'] = $this->truncation->value;
        }

        if (!empty($parameters)) {
            $payload['parameters'] = $parameters;
        }

        return $payload;
    }
}
