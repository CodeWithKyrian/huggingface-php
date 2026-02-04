<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for sentence similarity requests.
 */
final class SentenceSimilarityBuilder
{
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
     * Execute the request and return similarity scores.
     *
     * @param string        $sourceSentence The source sentence to compare against
     * @param array<string> $sentences      The sentences to compare with the source
     *
     * @return array<float> Similarity scores for each sentence
     */
    public function execute(string $sourceSentence, array $sentences): array
    {
        $payload = [
            'inputs' => [
                'source_sentence' => $sourceSentence,
                'sentences' => $sentences,
            ],
        ];
        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        return $this->helper->getResponse($response->json());
    }
}
