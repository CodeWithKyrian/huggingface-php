<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\ClassificationOutput;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for zero-shot classification requests.
 */
final class ZeroShotClassificationBuilder
{
    private bool $multiLabel = false;
    private ?string $hypothesisTemplate = null;

    /** @var array<string, mixed> */
    private array $customParameters = [];

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
     * Enable multi-label classification.
     *
     * When enabled, each label is classified independently and probabilities
     * are normalized for each candidate. When disabled (default), scores are
     * normalized such that the sum of the label likelihoods for each sequence is 1.
     */
    public function multiLabel(bool $multiLabel = true): self
    {
        $this->multiLabel = $multiLabel;

        return $this;
    }

    /**
     * Set the hypothesis template.
     *
     * @param string $template The sentence used in conjunction with candidateLabels to attempt the
     *                         classification by replacing the placeholder with the candidate labels. Eg. "This text is about {}"
     */
    public function hypothesisTemplate(string $template): self
    {
        $this->hypothesisTemplate = $template;

        return $this;
    }

    /**
     * Add a custom parameter.
     *
     * Use this for model-specific parameters not covered by the standard API.
     */
    public function withParameter(string $key, mixed $value): self
    {
        $this->customParameters[$key] = $value;

        return $this;
    }

    /**
     * Add multiple custom parameters.
     *
     * @param array<string, mixed> $parameters
     */
    public function withParameters(array $parameters): self
    {
        $this->customParameters = array_merge($this->customParameters, $parameters);

        return $this;
    }

    /**
     * Execute the request and return classification results.
     *
     * @param string        $inputs          Text to classify
     * @param array<string> $candidateLabels The possible class labels
     *
     * @return array<ClassificationOutput>
     */
    public function execute(string $inputs, array $candidateLabels): array
    {
        $parameters = array_merge($this->customParameters, [
            'candidate_labels' => $candidateLabels,
        ]);

        if ($this->multiLabel) {
            $parameters['multi_label'] = $this->multiLabel;
        }

        if (null !== $this->hypothesisTemplate) {
            $parameters['hypothesis_template'] = $this->hypothesisTemplate;
        }

        $payload = [
            'inputs' => $inputs,
            'parameters' => $parameters,
        ];

        $body = $this->helper->preparePayload($payload, $this->model);
        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        $data = $this->helper->getResponse($response->json());

        // Handle different response formats
        if (isset($data['labels'], $data['scores'])) {
            // Format: { labels: [...], scores: [...] }
            $results = [];
            foreach ($data['labels'] as $i => $label) {
                $results[] = ClassificationOutput::fromArray([
                    'label' => $label,
                    'score' => $data['scores'][$i] ?? 0.0,
                ]);
            }

            return $results;
        }

        // Format: [{ label: ..., score: ... }, ...]
        return array_map(
            static fn (array $item) => ClassificationOutput::fromArray($item),
            $data
        );
    }
}
