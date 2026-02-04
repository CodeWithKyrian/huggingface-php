<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\FalAi;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;

/**
 * FalAi image-to-image provider.
 *
 * Uses queue-based async processing. Accepts base64 image or URL input.
 *
 * @see https://fal.ai/models/image-to-image
 */
class ImageToImageProvider extends BaseFalAiProvider
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
        $payload = [];

        // Handle image input - expects base64 data URL or URL
        if (isset($args['image_url'])) {
            $payload['image_url'] = $args['image_url'];
            // Also include as array for endpoints expecting image_urls
            $payload['image_urls'] = [$args['image_url']];
        }

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
        // Handle resolved response with images array
        if (\is_array($response) && isset($response['images'][0]['url'])) {
            return $response['images'][0]['url'];
        }

        return $response;
    }
}
