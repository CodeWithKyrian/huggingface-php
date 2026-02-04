<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\FalAi;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;

/**
 * FalAi text-to-video provider.
 *
 * Uses queue-based async processing. Returns video URLs.
 *
 * @see https://fal.ai/models/text-to-video
 */
class TextToVideoProvider extends BaseFalAiProvider
{
    protected bool $useQueue = true;

    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string {
        $base = 'https://queue.fal.run';

        if (AuthMethod::ProviderKey !== $authMethod) {
            $base = $this->provider->routerBaseUrl();
        }

        $route = $this->makeRoute($model, $task);

        if (AuthMethod::ProviderKey !== $authMethod) {
            return "{$base}/{$route}?_subdomain=queue";
        }

        return "{$base}/{$route}";
    }

    public function preparePayload(array $args, string $model): array
    {
        $payload = [
            'prompt' => $args['inputs'] ?? $args['prompt'] ?? '',
        ];

        // Handle num_inference_steps -> steps mapping
        if (isset($args['parameters']['num_inference_steps'])) {
            $payload['steps'] = $args['parameters']['num_inference_steps'];
            unset($args['parameters']['num_inference_steps']);
        }

        // Merge remaining parameters
        if (isset($args['parameters'])) {
            $payload = array_merge($payload, $args['parameters']);
        }

        // Remove HF-specific keys
        unset($payload['inputs'], $payload['parameters']);

        return $payload;
    }

    public function getResponse(mixed $response): mixed
    {
        // Handle resolved response with video URL
        if (\is_array($response) && isset($response['video']['url'])) {
            return $response['video']['url'];
        }

        // Queue response - return for polling
        return $response;
    }
}
