<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Nscale;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelper;

/**
 * Nscale text-to-image provider.
 *
 * Returns base64-encoded images using b64_json format.
 *
 * @see https://www.nscale.com/docs/
 */
class TextToImageProvider extends ProviderHelper
{
    private const BASE_URL = 'https://inference.api.nscale.com';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Nscale, self::BASE_URL);
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
            'model' => $model,
            'prompt' => $args['inputs'] ?? $args['prompt'] ?? '',
            'response_format' => 'b64_json',
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
                'Expected array response from Nscale text-to-image API',
                $response
            );
        }

        if (!isset($response['data']) || !\is_array($response['data']) || empty($response['data'])) {
            throw new OutputValidationException(
                'Expected "data" array in text-to-image response',
                $response
            );
        }

        $b64Json = $response['data'][0]['b64_json'] ?? null;
        if (!\is_string($b64Json)) {
            throw new OutputValidationException(
                'Expected b64_json string in response',
                $response
            );
        }

        // Return base64 decoded binary data
        return base64_decode($b64Json);
    }
}
