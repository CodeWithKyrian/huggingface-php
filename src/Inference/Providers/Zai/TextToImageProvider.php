<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Zai;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelper;

/**
 * ZAI (z.ai) text-to-image provider.
 *
 * Uses async polling API - submits job, then polls for result.
 *
 * @see https://api.z.ai
 */
class TextToImageProvider extends ProviderHelper
{
    private const BASE_URL = 'https://api.z.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Zai, self::BASE_URL);
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
        return 'api/paas/v4/async/images/generations';
    }

    public function prepareHeaders(
        AuthMethod $authMethod,
        ?string $accessToken = null,
        bool $isBinary = false,
        ?string $billTo = null,
    ): array {
        $headers = parent::prepareHeaders($authMethod, $accessToken, $isBinary, $billTo);

        $headers['x-source-channel'] = 'hugging_face';
        $headers['accept-language'] = 'en-US,en';

        return $headers;
    }

    public function preparePayload(array $args, string $model): array
    {
        $payload = ['model' => $model];

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
        // Note: Full async polling would require HTTP client access.
        // This implementation returns the task ID for the caller to poll.
        // For proper async support, the caller needs to poll the result endpoint.

        if (!\is_array($response) || !isset($response['id'])) {
            throw new OutputValidationException(
                'Expected ZAI response with id for async result',
                $response
            );
        }

        if (isset($response['task_status']) && 'FAIL' === $response['task_status']) {
            throw new OutputValidationException('ZAI API returned task status: FAIL', $response);
        }

        // If we have image_result already (polled result), extract the image
        if (isset($response['image_result'][0]['url'])) {
            $imageUrl = $response['image_result'][0]['url'];
            $content = @file_get_contents($imageUrl);

            if (false === $content) {
                throw new OutputValidationException("Failed to fetch ZAI image from: {$imageUrl}");
            }

            return $content;
        }

        // Return task info for async handling
        return [
            'task_id' => $response['id'],
            'task_status' => $response['task_status'] ?? 'PROCESSING',
            'poll_url' => "api/paas/v4/async-result/{$response['id']}",
        ];
    }
}
