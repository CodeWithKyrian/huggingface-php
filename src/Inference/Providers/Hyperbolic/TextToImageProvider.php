<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Hyperbolic;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelper;

/**
 * Hyperbolic text-to-image provider.
 *
 * Returns base64-encoded images which are decoded to binary.
 *
 * @see https://docs.hyperbolic.xyz/docs
 */
class TextToImageProvider extends ProviderHelper
{
    private const BASE_URL = 'https://api.hyperbolic.xyz';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Hyperbolic, self::BASE_URL);
    }

    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string {
        $base = $this->makeBaseUrl($authMethod, $endpointUrl);

        return "{$base}/v1/images/generations";
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return 'v1/images/generations';
    }

    public function preparePayload(array $args, string $model): array
    {
        $payload = [
            'model_name' => $model,
            'prompt' => $args['inputs'] ?? $args['prompt'] ?? '',
        ];

        // Merge additional parameters
        if (isset($args['parameters'])) {
            $payload = array_merge($payload, $args['parameters']);
        }

        // Remove inputs/parameters keys if present
        unset($payload['inputs'], $payload['parameters']);

        return $payload;
    }

    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response)) {
            throw new OutputValidationException(
                'Expected array response from Hyperbolic text-to-image API',
                $response
            );
        }

        if (!isset($response['images']) || !\is_array($response['images']) || empty($response['images'])) {
            throw new OutputValidationException(
                'Expected "images" array in text-to-image response',
                $response
            );
        }

        $imageData = $response['images'][0]['image'] ?? null;
        if (!\is_string($imageData)) {
            throw new OutputValidationException(
                'Expected base64 image string in response',
                $response
            );
        }

        // Return base64 decoded binary data
        return base64_decode($imageData);
    }
}
