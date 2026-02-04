<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Base provider for OpenAI-compatible embeddings APIs.
 *
 * Handles the standard embeddings endpoint format used by Nebius, Sambanova, etc.
 */
abstract class BaseFeatureExtractionProvider extends ProviderHelper
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
        return 'v1/embeddings';
    }

    public function preparePayload(array $args, string $model): array
    {
        $payload = ['model' => $model];

        if (isset($args['inputs'])) {
            $payload['input'] = $args['inputs'];
            unset($args['inputs']);
        }

        return array_merge($payload, $args);
    }

    public function getResponse(mixed $response): mixed
    {
        // OpenAI embeddings format: { data: [{ embedding: [...] }] }
        if (\is_array($response) && isset($response['data']) && \is_array($response['data'])) {
            return array_map(
                static fn (array $item) => $item['embedding'] ?? [],
                $response['data']
            );
        }

        throw new OutputValidationException(
            'Expected embeddings response with data[].embedding',
            $response
        );
    }
}
