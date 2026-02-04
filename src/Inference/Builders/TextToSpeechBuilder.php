<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for text-to-speech requests.
 */
final class TextToSpeechBuilder
{
    private ?bool $doSample = null;
    private bool|string|null $earlyStopping = null;
    private ?float $epsilonCutoff = null;
    private ?float $etaCutoff = null;
    private ?int $maxLength = null;
    private ?int $maxNewTokens = null;
    private ?int $minLength = null;
    private ?int $minNewTokens = null;
    private ?int $numBeamGroups = null;
    private ?int $numBeams = null;
    private ?float $penaltyAlpha = null;
    private ?float $temperature = null;
    private ?int $topK = null;
    private ?float $topP = null;
    private ?float $typicalP = null;
    private ?bool $useCache = null;

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

    public function doSample(bool $doSample): self
    {
        $this->doSample = $doSample;

        return $this;
    }

    /**
     * Controls the stopping condition for beam-based methods.
     *
     * @param bool|string $earlyStopping boolean or "never"
     */
    public function earlyStopping(bool|string $earlyStopping): self
    {
        $this->earlyStopping = $earlyStopping;

        return $this;
    }

    public function epsilonCutoff(float $epsilonCutoff): self
    {
        $this->epsilonCutoff = $epsilonCutoff;

        return $this;
    }

    public function etaCutoff(float $etaCutoff): self
    {
        $this->etaCutoff = $etaCutoff;

        return $this;
    }

    public function maxLength(int $maxLength): self
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function maxNewTokens(int $maxNewTokens): self
    {
        $this->maxNewTokens = $maxNewTokens;

        return $this;
    }

    public function minLength(int $minLength): self
    {
        $this->minLength = $minLength;

        return $this;
    }

    public function minNewTokens(int $minNewTokens): self
    {
        $this->minNewTokens = $minNewTokens;

        return $this;
    }

    public function numBeamGroups(int $numBeamGroups): self
    {
        $this->numBeamGroups = $numBeamGroups;

        return $this;
    }

    public function numBeams(int $numBeams): self
    {
        $this->numBeams = $numBeams;

        return $this;
    }

    public function penaltyAlpha(float $penaltyAlpha): self
    {
        $this->penaltyAlpha = $penaltyAlpha;

        return $this;
    }

    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function topK(int $topK): self
    {
        $this->topK = $topK;

        return $this;
    }

    public function topP(float $topP): self
    {
        $this->topP = $topP;

        return $this;
    }

    public function typicalP(float $typicalP): self
    {
        $this->typicalP = $typicalP;

        return $this;
    }

    public function useCache(bool $useCache): self
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * Execute the request and return audio data.
     *
     * @param string $inputs Text to synthesize
     *
     * @return string Raw audio data (typically WAV format)
     */
    public function execute(string $inputs): string
    {
        $payload = ['inputs' => $inputs];

        $generationParameters = array_filter([
            'do_sample' => $this->doSample,
            'early_stopping' => $this->earlyStopping,
            'epsilon_cutoff' => $this->epsilonCutoff,
            'eta_cutoff' => $this->etaCutoff,
            'max_length' => $this->maxLength,
            'max_new_tokens' => $this->maxNewTokens,
            'min_length' => $this->minLength,
            'min_new_tokens' => $this->minNewTokens,
            'num_beam_groups' => $this->numBeamGroups,
            'num_beams' => $this->numBeams,
            'penalty_alpha' => $this->penaltyAlpha,
            'temperature' => $this->temperature,
            'top_k' => $this->topK,
            'top_p' => $this->topP,
            'typical_p' => $this->typicalP,
            'use_cache' => $this->useCache,
        ], static fn ($v) => null !== $v);

        if (!empty($generationParameters)) {
            $payload['parameters'] = ['generation_parameters' => $generationParameters];
        }

        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        // TTS returns binary audio data, not JSON
        return $response->body();
    }

    /**
     * Execute and save to a file.
     *
     * @param string $inputs Text to synthesize
     * @param string $path   Path to save the audio file
     *
     * @return string The path where the file was saved
     */
    public function save(string $inputs, string $path): string
    {
        $audioData = $this->execute($inputs);
        file_put_contents($path, $audioData);

        return $path;
    }
}
