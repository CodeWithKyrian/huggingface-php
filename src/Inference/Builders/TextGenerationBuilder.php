<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\TextGenerationOutput;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for text generation requests.
 */
final class TextGenerationBuilder
{
    private ?string $adapterId = null;
    private ?int $bestOf = null;
    private ?bool $decoderInputDetails = null;
    private ?bool $details = null;
    private ?float $frequencyPenalty = null;

    /** @var null|array<string, mixed> */
    private ?array $grammar = null;
    private ?int $topNTokens = null;
    private ?int $truncate = null;
    private ?float $typicalP = null;
    private ?bool $watermark = null;

    private ?int $maxNewTokens = null;
    private ?float $temperature = null;
    private ?float $topP = null;
    private ?float $topK = null;
    private ?float $repetitionPenalty = null;
    private ?bool $doSample = null;
    private bool $returnFullText = false;
    private ?int $seed = null;

    /** @var null|array<string>|string */
    private array|string|null $stop = null;

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

    public function adapterId(string $adapterId): self
    {
        $this->adapterId = $adapterId;

        return $this;
    }

    public function bestOf(int $bestOf): self
    {
        $this->bestOf = $bestOf;

        return $this;
    }

    public function decoderInputDetails(bool $decoderInputDetails): self
    {
        $this->decoderInputDetails = $decoderInputDetails;

        return $this;
    }

    public function details(bool $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function frequencyPenalty(float $frequencyPenalty): self
    {
        $this->frequencyPenalty = $frequencyPenalty;

        return $this;
    }

    /**
     * @param array<string, mixed> $grammar
     */
    public function grammar(array $grammar): self
    {
        $this->grammar = $grammar;

        return $this;
    }

    /**
     * Set the maximum number of tokens to generate.
     */
    public function maxNewTokens(int $maxNewTokens): self
    {
        $this->maxNewTokens = $maxNewTokens;

        return $this;
    }

    /**
     * Set the sampling temperature (0.0 - 2.0).
     */
    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Set nucleus sampling parameter (0.0 - 1.0).
     */
    public function topP(float $topP): self
    {
        $this->topP = $topP;

        return $this;
    }

    /**
     * Set top-k sampling parameter.
     */
    public function topK(float $topK): self
    {
        $this->topK = $topK;

        return $this;
    }

    public function topNTokens(int $topNTokens): self
    {
        $this->topNTokens = $topNTokens;

        return $this;
    }

    public function truncate(int $truncate): self
    {
        $this->truncate = $truncate;

        return $this;
    }

    public function typicalP(float $typicalP): self
    {
        $this->typicalP = $typicalP;

        return $this;
    }

    public function watermark(bool $watermark): self
    {
        $this->watermark = $watermark;

        return $this;
    }

    /**
     * Set the repetition penalty.
     *
     * Values > 1.0 penalize repetition.
     */
    public function repetitionPenalty(float $penalty): self
    {
        $this->repetitionPenalty = $penalty;

        return $this;
    }

    /**
     * Enable sampling (vs greedy decoding).
     */
    public function doSample(bool $doSample = true): self
    {
        $this->doSample = $doSample;

        return $this;
    }

    /**
     * Return the full text including the prompt.
     */
    public function returnFullText(bool $returnFullText = true): self
    {
        $this->returnFullText = $returnFullText;

        return $this;
    }

    /**
     * Set random seed for reproducibility.
     */
    public function seed(int $seed): self
    {
        $this->seed = $seed;

        return $this;
    }

    /**
     * Set stop sequence(s).
     *
     * @param array<string>|string $stop
     */
    public function stop(array|string $stop): self
    {
        $this->stop = $stop;

        return $this;
    }

    /**
     * Execute the request and return the response.
     *
     * @param string $prompt The prompt to generate from
     */
    public function execute(string $prompt): TextGenerationOutput
    {
        $payload = $this->buildPayload($prompt);
        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        $data = $this->helper->getResponse($response->json());

        return TextGenerationOutput::fromArray($data);
    }

    /**
     * Build the request payload.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(string $prompt): array
    {
        $payload = ['inputs' => $prompt];

        $parameters = array_filter([
            'adapter_id' => $this->adapterId,
            'best_of' => $this->bestOf,
            'decoder_input_details' => $this->decoderInputDetails,
            'details' => $this->details,
            'do_sample' => $this->doSample,
            'frequency_penalty' => $this->frequencyPenalty,
            'grammar' => $this->grammar,
            'max_new_tokens' => $this->maxNewTokens,
            'repetition_penalty' => $this->repetitionPenalty,
            'return_full_text' => $this->returnFullText ? true : null,
            'seed' => $this->seed,
            'temperature' => $this->temperature,
            'top_k' => $this->topK,
            'top_n_tokens' => $this->topNTokens,
            'top_p' => $this->topP,
            'truncate' => $this->truncate,
            'typical_p' => $this->typicalP,
            'watermark' => $this->watermark,
        ], static fn ($v) => null !== $v);

        if (null !== $this->stop) {
            $parameters['stop'] = \is_array($this->stop) ? $this->stop : [$this->stop];
        }

        if (!empty($parameters)) {
            $payload['parameters'] = $parameters;
        }

        return $payload;
    }
}
