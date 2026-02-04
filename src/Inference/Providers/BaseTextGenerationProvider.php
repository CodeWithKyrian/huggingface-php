<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Base provider for OpenAI-compatible text completion APIs.
 *
 * Handles the standard completions endpoint format used by Together, Nebius, etc.
 */
abstract class BaseTextGenerationProvider extends ProviderHelper
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
        return 'v1/completions';
    }

    public function preparePayload(array $args, string $model): array
    {
        $payload = ['model' => $model];

        if (isset($args['inputs'])) {
            $payload['prompt'] = $args['inputs'];
            unset($args['inputs']);
        }

        return array_merge($payload, $args);
    }

    public function getResponse(mixed $response): mixed
    {
        // OpenAI completions format: { choices: [{ text: "..." }] }
        if (\is_array($response) && isset($response['choices'][0]['text'])) {
            return ['generated_text' => $response['choices'][0]['text']];
        }

        throw new OutputValidationException(
            'Expected completions response with choices[0].text',
            $response
        );
    }
}
