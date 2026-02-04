<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Base provider for OpenAI-compatible image generation APIs.
 *
 * Handles the standard images/generations endpoint format used by Together, Nebius, etc.
 */
abstract class BaseTextToImageProvider extends ProviderHelper
{
    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string {
        $base = $this->makeBaseUrl($authMethod, $endpointUrl);

        return "{$base}/{$this->makeRoute($model, $task)}";
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return 'v1/images/generations';
    }

    public function preparePayload(array $args, string $model): array
    {
        $payload = [
            'model' => $model,
            'response_format' => 'b64_json',
        ];

        if (isset($args['inputs'])) {
            $payload['prompt'] = $args['inputs'];
            unset($args['inputs']);
        }

        // Flatten parameters if present
        if (isset($args['parameters']) && \is_array($args['parameters'])) {
            $payload = array_merge($payload, $args['parameters']);
            unset($args['parameters']);
        }

        return array_merge($payload, $args);
    }

    public function getResponse(mixed $response): mixed
    {
        // OpenAI images format: { data: [{ b64_json: "..." } or { url: "..." }] }
        if (\is_array($response) && isset($response['data'][0])) {
            $first = $response['data'][0];

            if (isset($first['b64_json'])) {
                return base64_decode($first['b64_json']);
            }

            if (isset($first['url'])) {
                return file_get_contents($first['url']);
            }
        }

        throw new OutputValidationException(
            'Expected image generation response with data[0].b64_json or data[0].url',
            $response
        );
    }
}
