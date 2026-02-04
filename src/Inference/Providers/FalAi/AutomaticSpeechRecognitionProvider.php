<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\FalAi;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * FalAi automatic speech recognition provider.
 *
 * Accepts audio input (base64 data URL) and returns transcribed text.
 *
 * @see https://fal.ai/models/speech-recognition
 */
class AutomaticSpeechRecognitionProvider extends BaseFalAiProvider
{
    public const SUPPORTED_AUDIO_TYPES = ['audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/x-wav'];

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
        $payload = [];

        // Handle audio input
        if (isset($args['inputs'])) {
            $base64 = $args['inputs'];
            $mimeType = $args['content_type'] ?? 'audio/wav';
            $payload['audio_url'] = "data:{$mimeType};base64,{$base64}";
        } elseif (isset($args['audio_url'])) {
            $payload['audio_url'] = $args['audio_url'];
        }

        // Merge parameters
        if (isset($args['parameters'])) {
            $payload = array_merge($payload, $args['parameters']);
        }

        // Remove HF-specific keys
        unset($payload['inputs'], $payload['parameters'], $payload['data'], $payload['content_type']);

        return $payload;
    }

    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response) || !isset($response['text'])) {
            throw new OutputValidationException(
                'Expected { text: string } from Fal.ai ASR API',
                $response
            );
        }

        return ['text' => $response['text']];
    }
}
