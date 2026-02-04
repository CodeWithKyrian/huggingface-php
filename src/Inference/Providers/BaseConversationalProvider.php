<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Base provider for OpenAI-compatible chat completion APIs.
 *
 * Most external providers (Together, Groq, Nebius, Cerebras, Fireworks, Sambanova)
 * follow the OpenAI chat completions API format with minor variations.
 */
abstract class BaseConversationalProvider extends ProviderHelper
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
        return 'v1/chat/completions';
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
