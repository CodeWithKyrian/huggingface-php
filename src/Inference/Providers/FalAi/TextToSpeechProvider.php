<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\FalAi;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * FalAi text-to-speech provider.
 *
 * Converts text to audio. Returns audio URL that needs to be fetched.
 *
 * @see https://fal.ai/models/text-to-speech
 */
class TextToSpeechProvider extends BaseFalAiProvider
{
    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string {
        $base = static::BASE_URL;

        if (AuthMethod::ProviderKey !== $authMethod) {
            $base = $this->provider->routerBaseUrl();
        }

        return "{$base}/{$this->makeRoute($model, $task)}";
    }

    public function preparePayload(array $args, string $model): array
    {
        $payload = [
            'text' => $args['inputs'] ?? $args['text'] ?? '',
        ];

        // Merge parameters
        if (isset($args['parameters'])) {
            $payload = array_merge($payload, $args['parameters']);
        }

        // Remove HF-specific keys
        unset($payload['inputs'], $payload['parameters']);

        return $payload;
    }

    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response) || !isset($response['audio']['url'])) {
            throw new OutputValidationException(
                'Expected { audio: { url: string } } from Fal.ai TTS API',
                $response
            );
        }

        // Return audio URL for download
        return $response['audio']['url'];
    }
}
