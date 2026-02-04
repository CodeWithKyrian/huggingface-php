<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Chat completion specific helper for HF Inference.
 */
class ChatProvider extends Provider
{
    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string {
        // If model is already a URL, handle it specially
        if (str_starts_with($model, 'http://') || str_starts_with($model, 'https://')) {
            $url = rtrim($model, '/');
            if (str_ends_with($url, '/v1')) {
                return $url.'/chat/completions';
            }
            if (!str_ends_with($url, '/chat/completions')) {
                return $url.'/v1/chat/completions';
            }

            return $url;
        }

        // HfInference always uses the router
        $base = null !== $endpointUrl
            ? rtrim($endpointUrl, '/')
            : 'https://router.huggingface.co/hf-inference';

        return "{$base}/models/{$model}/v1/chat/completions";
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return "models/{$model}/v1/chat/completions";
    }

    public function preparePayload(array $args, string $model): array
    {
        return array_merge($args, ['model' => $model]);
    }

    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response)) {
            throw new OutputValidationException(
                'Expected array response from chat completion API',
                $response
            );
        }

        if (!isset($response['choices']) || !\is_array($response['choices'])) {
            throw new OutputValidationException(
                'Expected "choices" array in chat completion response',
                $response
            );
        }

        return $response;
    }
}
