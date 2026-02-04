<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\TranslationOutput;
use Codewithkyrian\HuggingFace\Inference\Enums\TruncationStrategy;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for translation requests.
 */
final class TranslationBuilder
{
    private ?bool $cleanUpTokenizationSpaces = null;
    private ?string $srcLang = null;
    private ?string $tgtLang = null;
    private ?TruncationStrategy $truncation = null;

    // Generation Parameters
    private ?int $maxNewTokens = null;
    private ?float $temperature = null;
    private ?float $topP = null;
    private ?int $topK = null;
    private ?float $repetitionPenalty = null;
    private ?bool $doSample = null;

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

    public function cleanUpTokenizationSpaces(bool $cleanUpTokenizationSpaces): self
    {
        $this->cleanUpTokenizationSpaces = $cleanUpTokenizationSpaces;

        return $this;
    }

    public function srcLang(string $srcLang): self
    {
        $this->srcLang = $srcLang;

        return $this;
    }

    public function tgtLang(string $tgtLang): self
    {
        $this->tgtLang = $tgtLang;

        return $this;
    }

    public function truncation(TruncationStrategy $truncation): self
    {
        $this->truncation = $truncation;

        return $this;
    }

    /**
     * Max new tokens to generate.
     */
    public function maxNewTokens(int $maxNewTokens): self
    {
        $this->maxNewTokens = $maxNewTokens;

        return $this;
    }

    /**
     * Sampling temperature.
     */
    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Nucleus sampling parameter.
     */
    public function topP(float $topP): self
    {
        $this->topP = $topP;

        return $this;
    }

    /**
     * Top-k sampling parameter.
     */
    public function topK(int $topK): self
    {
        $this->topK = $topK;

        return $this;
    }

    /**
     * Repetition penalty.
     */
    public function repetitionPenalty(float $repetitionPenalty): self
    {
        $this->repetitionPenalty = $repetitionPenalty;

        return $this;
    }

    /**
     * Enable sampling.
     */
    public function doSample(bool $doSample = true): self
    {
        $this->doSample = $doSample;

        return $this;
    }

    /**
     * Execute the request and return the translation.
     *
     * @param string $inputs Text to translate
     */
    public function execute(string $inputs): TranslationOutput
    {
        $payload = ['inputs' => $inputs];
        $parameters = [];

        if (null !== $this->cleanUpTokenizationSpaces) {
            $parameters['clean_up_tokenization_spaces'] = $this->cleanUpTokenizationSpaces;
        }

        if (null !== $this->srcLang) {
            $parameters['src_lang'] = $this->srcLang;
        }

        if (null !== $this->tgtLang) {
            $parameters['tgt_lang'] = $this->tgtLang;
        }

        if (null !== $this->truncation) {
            $parameters['truncation'] = $this->truncation->value;
        }

        // Generation Parameters
        $generateParameters = array_filter([
            'max_new_tokens' => $this->maxNewTokens,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'top_k' => $this->topK,
            'repetition_penalty' => $this->repetitionPenalty,
            'do_sample' => $this->doSample,
        ], static fn ($v) => null !== $v);

        if (!empty($generateParameters)) {
            $parameters['generate_parameters'] = $generateParameters;
        }

        if (!empty($parameters)) {
            $payload['parameters'] = $parameters;
        }

        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        $data = $this->helper->getResponse($response->json());

        return TranslationOutput::fromArray($data);
    }
}
