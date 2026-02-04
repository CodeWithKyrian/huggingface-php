<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\AutomaticSpeechRecognitionOutput;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for automatic speech recognition requests.
 */
final class AutomaticSpeechRecognitionBuilder
{
    private ?bool $returnTimestamps = null;
    private ?bool $doSample = null;
    private bool|string|null $earlyStopping = null;
    private ?int $maxNewTokens = null;
    private ?int $maxLength = null;
    private ?float $temperature = null;
    private ?float $topP = null;
    private ?float $topK = null;
    private ?int $numBeams = null;

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
     * Whether to output corresponding timestamps with the generated text.
     */
    public function returnTimestamps(bool $returnTimestamps = true): self
    {
        $this->returnTimestamps = $returnTimestamps;

        return $this;
    }

    /**
     * Whether to use sampling instead of greedy decoding.
     */
    public function doSample(bool $doSample = true): self
    {
        $this->doSample = $doSample;

        return $this;
    }

    /**
     * Controls the stopping condition for beam-based methods.
     */
    public function earlyStopping(bool|string $earlyStopping): self
    {
        $this->earlyStopping = $earlyStopping;

        return $this;
    }

    /**
     * The maximum number of tokens to generate.
     */
    public function maxNewTokens(int $maxNewTokens): self
    {
        $this->maxNewTokens = $maxNewTokens;

        return $this;
    }

    /**
     * The maximum length (in tokens) of the generated text.
     */
    public function maxLength(int $maxLength): self
    {
        $this->maxLength = $maxLength;

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
     * Set nucleus sampling parameter (top_p).
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

    /**
     * Number of beams to use for beam search.
     */
    public function numBeams(int $numBeams): self
    {
        $this->numBeams = $numBeams;

        return $this;
    }

    /**
     * Execute the request and return the transcription.
     *
     * @param string $audioInput Path to audio file, base64 data, or raw bytes
     */
    public function execute(string $audioInput): AutomaticSpeechRecognitionOutput
    {
        $payload = $this->buildPayload($audioInput);
        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        $data = $this->helper->getResponse($response->json());

        return AutomaticSpeechRecognitionOutput::fromArray($data);
    }

    /**
     * Resolve audio input to base64 string and mime type.
     *
     * @return array{0: string, 1: string} [base64, mimeType]
     */
    private function resolveAudioInput(string $audioInput): array
    {
        // 1. Data URI
        if (str_starts_with($audioInput, 'data:')) {
            $parts = explode(',', $audioInput, 2);
            $mimeType = explode(';', substr($parts[0], 5))[0] ?? 'audio/wav';

            return [$parts[1] ?? '', $mimeType];
        }

        // 2. URL or File
        $content = '';
        $mimeType = 'audio/wav'; // Default fallback

        if (filter_var($audioInput, \FILTER_VALIDATE_URL)) {
            $content = file_get_contents($audioInput);
            $extension = pathinfo(parse_url($audioInput, \PHP_URL_PATH) ?? '', \PATHINFO_EXTENSION);
        } elseif (file_exists($audioInput)) {
            $content = file_get_contents($audioInput);
            $extension = pathinfo($audioInput, \PATHINFO_EXTENSION);
        } else {
            if (base64_encode(base64_decode($audioInput, true)) === $audioInput) {
                return [$audioInput, 'audio/wav'];
            }
            $content = $audioInput;
            $extension = 'wav';
        }

        if (!empty($extension)) {
            $mimeType = match (strtolower($extension)) {
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'flac' => 'audio/flac',
                'ogg' => 'audio/ogg',
                'm4a' => 'audio/mp4',
                'webm' => 'audio/webm',
                default => 'audio/wav',
            };
        }

        return [base64_encode($content), $mimeType];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $audioInput): array
    {
        [$base64, $mimeType] = $this->resolveAudioInput($audioInput);

        $payload = [
            'inputs' => $base64,
            'content_type' => $mimeType,
        ];

        $parameters = [];
        $generationParameters = [];

        if (null !== $this->returnTimestamps) {
            $parameters['return_timestamps'] = $this->returnTimestamps;
        }

        if (null !== $this->doSample) {
            $generationParameters['do_sample'] = $this->doSample;
        }
        if (null !== $this->earlyStopping) {
            $generationParameters['early_stopping'] = $this->earlyStopping;
        }
        if (null !== $this->maxNewTokens) {
            $generationParameters['max_new_tokens'] = $this->maxNewTokens;
        }
        if (null !== $this->maxLength) {
            $generationParameters['max_length'] = $this->maxLength;
        }
        if (null !== $this->temperature) {
            $generationParameters['temperature'] = $this->temperature;
        }
        if (null !== $this->topP) {
            $generationParameters['top_p'] = $this->topP;
        }
        if (null !== $this->topK) {
            $generationParameters['top_k'] = $this->topK;
        }
        if (null !== $this->numBeams) {
            $generationParameters['num_beams'] = $this->numBeams;
        }

        if (!empty($generationParameters)) {
            $parameters['generation_parameters'] = $generationParameters;
        }

        if (!empty($parameters)) {
            $payload['parameters'] = $parameters;
        }

        return $payload;
    }
}
