<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\AutoRouter;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\RoutingException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelper;

/**
 * Provider helper for the auto-router.
 *
 * The auto-router automatically selects the best available provider
 * for a given model, routing requests through router.huggingface.co.
 *
 * Only works with HF tokens - cannot be used with provider keys.
 */
class Provider extends ProviderHelper
{
    private const ROUTER_BASE = 'https://router.huggingface.co';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Auto, self::ROUTER_BASE);
    }

    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string {
        // Auto-router requires HF token
        if (AuthMethod::HfToken !== $authMethod) {
            throw new RoutingException(
                'Auto-router requires a Hugging Face token. '
                .'Provider keys cannot be used for automatic provider selection.'
            );
        }

        $base = null !== $endpointUrl
            ? rtrim($endpointUrl, '/')
            : self::ROUTER_BASE;

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
        return $response;
    }
}
