<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\FillMaskOutput;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for fill mask requests.
 */
final class FillMaskBuilder
{
    private ?int $topK = null;

    /** @var null|string[] */
    private ?array $targets = null;

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
     * Limit the scores to the passed targets instead of looking up in the whole vocabulary.
     *
     * @param string[] $targets
     */
    public function targets(array $targets): self
    {
        $this->targets = $targets;

        return $this;
    }

    /**
     * Overrides the number of predictions to return.
     */
    public function topK(int $topK): self
    {
        $this->topK = $topK;

        return $this;
    }

    /**
     * Execute the request and return fill mask predictions.
     *
     * @param string $inputs Text with [MASK] tokens
     *
     * @return array<FillMaskOutput>
     */
    public function execute(string $inputs): array
    {
        $payload = ['inputs' => $inputs];

        $parameters = array_filter([
            'targets' => $this->targets,
            'top_k' => $this->topK,
        ], static fn ($v) => null !== $v);

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
            static fn (array $item) => FillMaskOutput::fromArray($item),
            $data
        );
    }
}
