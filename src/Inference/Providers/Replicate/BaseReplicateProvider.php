<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Replicate;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelper;

/**
 * Base class for Replicate providers.
 *
 * Replicate uses a unique API pattern with polling/wait model and
 * different response structures compared to OpenAI-compatible providers.
 */
abstract class BaseReplicateProvider extends ProviderHelper
{
    private const BASE_URL = 'https://api.replicate.com';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Replicate, self::BASE_URL);
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
        if (str_contains($model, ':')) {
            return 'v1/predictions';
        }

        return "v1/models/{$model}/predictions";
    }

    /**
     * Prepare headers with Replicate's wait preference.
     */
    public function prepareHeaders(
        AuthMethod $authMethod,
        ?string $accessToken = null,
        bool $isBinary = false,
        ?string $billTo = null,
    ): array {
        $headers = parent::prepareHeaders($authMethod, $accessToken, $isBinary, $billTo);

        // Replicate-specific: wait for result instead of polling
        $headers['Prefer'] = 'wait';

        return $headers;
    }

    /**
     * Extract version from model string if present.
     */
    protected function extractVersion(string $model): ?string
    {
        if (str_contains($model, ':')) {
            return explode(':', $model)[1];
        }

        return null;
    }

    /**
     * Fetch content from a Replicate output URL.
     */
    protected function fetchOutput(string $url): string
    {
        $content = @file_get_contents($url);

        if (false === $content) {
            throw new OutputValidationException("Failed to fetch Replicate output from URL: {$url}");
        }

        return $content;
    }
}
