<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\FalAi;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * FalAi text-to-image provider.
 *
 * Uses queue-based async processing. Returns image URLs that need to be fetched.
 *
 * @see https://fal.ai/models/text-to-image
 */
class TextToImageProvider extends BaseFalAiProvider
{
    protected bool $useQueue = true;

    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string {
        // Use queue URL for text-to-image
        $base = 'https://queue.fal.run';

        if (AuthMethod::ProviderKey !== $authMethod) {
            // Use HF router
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
        // Response is queue data - the actual polling happens in the builder/client
        // For simplicity, we handle the case where the result is already resolved
        if (\is_array($response) && isset($response['images'])) {
            // Already resolved - extract first image URL
            $imageUrl = $response['images'][0]['url'] ?? null;
            if (!$imageUrl) {
                throw new OutputValidationException(
                    'Expected images array with url property in Fal.ai response',
                    $response
                );
            }

            // Return the URL - actual download happens in builder
            return $imageUrl;
        }

        // Queue response - return as-is for handling by queue polling
        return $response;
    }
}
