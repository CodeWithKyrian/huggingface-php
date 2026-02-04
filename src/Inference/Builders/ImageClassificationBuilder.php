<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\ClassificationOutput;
use Codewithkyrian\HuggingFace\Inference\Enums\ClassificationOutputTransform;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for image classification requests.
 */
final class ImageClassificationBuilder
{
    private ?ClassificationOutputTransform $functionToApply = null;
    private ?int $topK = null;

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
     * The function to apply to the model outputs in order to retrieve the scores.
     */
    public function functionToApply(ClassificationOutputTransform $function): self
    {
        $this->functionToApply = $function;

        return $this;
    }

    /**
     * Limits the output to the top K most probable classes.
     */
    public function topK(int $topK): self
    {
        $this->topK = $topK;

        return $this;
    }

    /**
     * Execute the request and return classification results.
     *
     * @param string $inputs URL or base64-encoded image
     *
     * @return array<ClassificationOutput>
     */
    public function execute(string $inputs): array
    {
        $payload = ['inputs' => $inputs];

        $parameters = array_filter([
            'function_to_apply' => $this->functionToApply?->value,
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
            static fn (array $item) => ClassificationOutput::fromArray($item),
            $data
        );
    }
}
