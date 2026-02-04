<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelper;

/**
 * Provider helper for Hugging Face's Inference API.
 *
 * This is the default provider that handles most HF-hosted models.
 * Requests are always routed through router.huggingface.co/hf-inference.
 */
class Provider extends ProviderHelper
{
    private const ROUTER_BASE = 'https://router.huggingface.co/hf-inference';

    /** Tasks that share the sentence-transformers pipeline */
    private const SENTENCE_TRANSFORMER_TASKS = [
        InferenceTask::FeatureExtraction,
        InferenceTask::SentenceSimilarity,
    ];

    public function __construct()
    {
        parent::__construct(InferenceProvider::HfInference, self::ROUTER_BASE);
    }

    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string {
        // If model is already a URL, use it directly
        if (str_starts_with($model, 'http://') || str_starts_with($model, 'https://')) {
            return $model;
        }

        // HfInference always uses the router, regardless of auth method
        $base = null !== $endpointUrl
            ? rtrim($endpointUrl, '/')
            : self::ROUTER_BASE;

        return "{$base}/{$this->makeRoute($model, $task)}";
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        // Feature extraction and sentence similarity share the same pipeline
        if (null !== $task && \in_array($task, self::SENTENCE_TRANSFORMER_TASKS, true)) {
            return "models/{$model}/pipeline/{$task->value}";
        }

        return "models/{$model}";
    }

    public function preparePayload(array $args, string $model): array
    {
        return $args;
    }

    public function getResponse(mixed $response): mixed
    {
        return $response;
    }
}
