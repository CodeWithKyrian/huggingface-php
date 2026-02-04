<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\RoutingException;

/**
 * Service for fetching and caching inference provider mappings from the Hub API.
 *
 * This determines which providers are available for a given model and task.
 */
final class InferenceRouter
{
    private const HF_HUB_URL = 'https://huggingface.co';

    /** @var array<string, InferenceProviderMapping[]> */
    private static array $cache = [];

    public function __construct(
        private readonly HttpConnector $http,
    ) {}

    /**
     * Fetch inference provider mappings for a model.
     *
     * @return InferenceProviderMapping[]
     *
     * @throws RoutingException If unable to fetch mappings
     */
    public function fetchForModel(string $modelId): array
    {
        if (isset(self::$cache[$modelId])) {
            return self::$cache[$modelId];
        }

        $url = self::HF_HUB_URL."/api/models/{$modelId}?expand[]=inferenceProviderMapping";

        try {
            $response = $this->http->get($url, []);
        } catch (\Throwable $e) {
            throw new RoutingException(
                "Failed to fetch inference provider mapping for model {$modelId}: {$e->getMessage()}"
            );
        }

        if (!$response->successful()) {
            throw new RoutingException(
                "Failed to fetch inference provider mapping for model {$modelId}: HTTP {$response->status()}"
            );
        }

        $data = $response->json();

        if (!isset($data['inferenceProviderMapping'])) {
            throw new RoutingException(
                "No inference provider information found for model {$modelId}."
            );
        }

        $mappings = $this->normalizeMappings($modelId, $data['inferenceProviderMapping']);
        self::$cache[$modelId] = $mappings;

        return $mappings;
    }

    /**
     * Get a viable mapping for a model, task, and optionally specific provider.
     *
     * When provider is null (auto-routing), returns the first viable provider.
     * Skips providers with "error" status, warns on "staging" status.
     *
     * @param string                 $modelId  The model ID to get mapping for
     * @param InferenceTask          $task     The task to perform
     * @param null|InferenceProvider $provider Specific provider, or null for auto-routing
     *
     * @throws RoutingException If no viable provider available or task not supported
     */
    public function getMapping(
        string $modelId,
        InferenceTask $task,
        ?InferenceProvider $provider = null,
    ): InferenceProviderMapping {
        $mappings = $this->fetchForModel($modelId);

        if (empty($mappings)) {
            throw new RoutingException(
                "No inference provider available for model {$modelId}."
            );
        }

        $errorProviders = [];
        $candidateMappings = null !== $provider
            ? array_filter($mappings, static fn ($m) => $m->provider === $provider)
            : $mappings;

        foreach ($candidateMappings as $mapping) {
            if ('error' === $mapping->status) {
                $errorProviders[] = $mapping->provider->value;

                // TODO: Add PSR logger warning when logger is available
                // $this->logger?->warning("Provider {$mapping->provider->value} is in error state for model {$modelId}. Skipping.");
                continue;
            }

            if ('staging' === $mapping->status) {
                // TODO: Add PSR logger warning when logger is available
                // $this->logger?->warning("Model {$modelId} is in staging mode for provider {$mapping->provider->value}. Meant for test purposes only.");
            }

            $equivalentTasks = $this->getEquivalentTasks($mapping->provider, $task);

            if (!\in_array($mapping->task, $equivalentTasks, true)) {
                if (null !== $provider) {
                    throw new RoutingException(
                        "Model {$modelId} is not supported for task {$task->value} and provider {$provider->value}. "
                        ."Supported task: {$mapping->task}."
                    );
                }

                continue;
            }

            return $mapping;
        }

        if (null !== $provider) {
            if (\in_array($provider->value, $errorProviders, true)) {
                throw new RoutingException(
                    "Provider {$provider->value} is in error state for model {$modelId}. Cannot use this provider."
                );
            }

            throw new RoutingException(
                "No viable mapping found for model {$modelId} with provider {$provider->value}."
            );
        }

        if (!empty($errorProviders) && \count($errorProviders) === \count($candidateMappings)) {
            throw new RoutingException(
                "All inference providers are in error state for model {$modelId}. "
                .'Error providers: '.implode(', ', $errorProviders).'.'
            );
        }

        throw new RoutingException(
            "No viable inference provider found for model {$modelId} with task {$task->value}."
        );
    }

    /**
     * Clear the mapping cache.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Normalize the API response to an array of mappings.
     *
     * @param null|array<mixed> $data
     *
     * @return InferenceProviderMapping[]
     */
    private function normalizeMappings(string $modelId, mixed $data): array
    {
        if (null === $data) {
            return [];
        }

        // If already an array of mapping objects
        if (\is_array($data) && isset($data[0]) && \is_array($data[0])) {
            return array_map(
                static fn (array $item) => InferenceProviderMapping::fromArray($item),
                $data
            );
        }

        // If it's a Record<provider, mapping> format (older API)
        if (\is_array($data) && !isset($data[0])) {
            $mappings = [];
            foreach ($data as $provider => $mapping) {
                if (\is_array($mapping)) {
                    $mappings[] = InferenceProviderMapping::fromArray([
                        'provider' => $provider,
                        'hfModelId' => $modelId,
                        'providerId' => $mapping['providerId'] ?? '',
                        'status' => $mapping['status'] ?? 'live',
                        'task' => $mapping['task'] ?? '',
                    ]);
                }
            }

            return $mappings;
        }

        return [];
    }

    /**
     * Get equivalent tasks for HF Inference (sentence-transformers compatibility).
     *
     * @return string[]
     */
    private function getEquivalentTasks(InferenceProvider $provider, InferenceTask $task): array
    {
        if (InferenceProvider::HfInference === $provider) {
            $sentenceTransformerTasks = [
                InferenceTask::FeatureExtraction->value,
                InferenceTask::SentenceSimilarity->value,
            ];

            if (\in_array($task->value, $sentenceTransformerTasks, true)) {
                return $sentenceTransformerTasks;
            }
        }

        return [$task->value];
    }
}
