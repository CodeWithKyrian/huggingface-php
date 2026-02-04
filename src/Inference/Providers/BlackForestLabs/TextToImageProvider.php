<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\BlackForestLabs;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelper;

/**
 * Black Forest Labs text-to-image provider (FLUX models).
 *
 * Uses polling API - submits job, then polls for result.
 *
 * @see https://docs.bfl.ml/
 */
class TextToImageProvider extends ProviderHelper
{
    private const BASE_URL = 'https://api.us1.bfl.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::BlackForestLabs, self::BASE_URL);
    }

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
        return "v1/{$model}";
    }

    public function preparePayload(array $args, string $model): array
    {
        $payload = [];

        if (isset($args['inputs'])) {
            $payload['prompt'] = $args['inputs'];
            unset($args['inputs']);
        }

        if (isset($args['parameters']) && \is_array($args['parameters'])) {
            $payload = array_merge($payload, $args['parameters']);
            unset($args['parameters']);
        }

        return array_merge($payload, $args);
    }

    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response)) {
            throw new OutputValidationException(
                'Expected Black Forest Labs response array',
                $response
            );
        }

        // If we have a polling_url, this is the initial response
        if (isset($response['polling_url'])) {
            return [
                'id' => $response['id'] ?? null,
                'polling_url' => $response['polling_url'],
                'status' => 'pending',
            ];
        }

        // If we have a completed result with sample URL
        if (
            isset($response['status']) && 'Ready' === $response['status']
            && isset($response['result']['sample'])
        ) {
            $imageUrl = $response['result']['sample'];
            $content = @file_get_contents($imageUrl);

            if (false === $content) {
                throw new OutputValidationException(
                    "Failed to fetch Black Forest Labs image from: {$imageUrl}"
                );
            }

            return $content;
        }

        throw new OutputValidationException(
            'Expected Black Forest Labs response with polling_url or completed result',
            $response
        );
    }
}
