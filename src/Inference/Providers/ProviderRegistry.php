<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\RoutingException;

/**
 * Registry mapping providers and tasks to their helpers.
 *
 * This is the central routing mechanism that determines which provider helper
 * to use for a given provider and task combination. Uses class-string references
 * for lazy instantiation.
 *
 * @internal
 */
final class ProviderRegistry
{
    /**
     * Provider/Task to ProviderHelper class mapping.
     *
     * @var array<string, array<string, class-string<ProviderHelperInterface>>>
     */
    private const PROVIDERS = [
        // HF Inference
        'hf-inference' => [
            // NLP Tasks
            'conversational' => HfInference\ChatProvider::class,
            'text-generation' => HfInference\TextGenerationProvider::class,
            'feature-extraction' => HfInference\FeatureExtractionProvider::class,
            'sentence-similarity' => HfInference\SentenceSimilarityProvider::class,
            'text-classification' => HfInference\TextClassificationProvider::class,
            'token-classification' => HfInference\Provider::class,
            'question-answering' => HfInference\QuestionAnsweringProvider::class,
            'summarization' => HfInference\SummarizationProvider::class,
            'translation' => HfInference\TranslationProvider::class,
            'fill-mask' => HfInference\FillMaskProvider::class,
            'zero-shot-classification' => HfInference\ZeroShotClassificationProvider::class,
            'table-question-answering' => HfInference\Provider::class,

            // Vision Tasks
            'image-classification' => HfInference\ImageClassificationProvider::class,
            'image-to-text' => HfInference\ImageToTextProvider::class,
            'text-to-image' => HfInference\Provider::class,
            'object-detection' => HfInference\Provider::class,
            'image-segmentation' => HfInference\Provider::class,
            'zero-shot-image-classification' => HfInference\ImageClassificationProvider::class,

            // Audio Tasks
            'automatic-speech-recognition' => HfInference\Provider::class,
            'audio-classification' => HfInference\Provider::class,
            'text-to-speech' => HfInference\Provider::class,

            // Multimodal Tasks
            'document-question-answering' => HfInference\Provider::class,
            'visual-question-answering' => HfInference\Provider::class,

            // Tabular Tasks
            'tabular-classification' => HfInference\Provider::class,
            'tabular-regression' => HfInference\Provider::class,
        ],

        // Auto router
        'auto' => [
            'conversational' => AutoRouter\ChatProvider::class,
        ],

        // Together
        'together' => [
            'conversational' => Together\ChatProvider::class,
            'text-generation' => Together\TextGenerationProvider::class,
            'text-to-image' => Together\TextToImageProvider::class,
        ],

        // Groq
        'groq' => [
            'conversational' => Groq\ChatProvider::class,
            'text-generation' => Groq\TextGenerationProvider::class,
        ],

        // Nebius
        'nebius' => [
            'conversational' => Nebius\ChatProvider::class,
            'text-generation' => Nebius\TextGenerationProvider::class,
            'feature-extraction' => Nebius\FeatureExtractionProvider::class,
            'text-to-image' => Nebius\TextToImageProvider::class,
        ],

        // Cerebras
        'cerebras' => [
            'conversational' => Cerebras\ChatProvider::class,
        ],

        // Fireworks
        'fireworks-ai' => [
            'conversational' => Fireworks\ChatProvider::class,
        ],

        // Sambanova
        'sambanova' => [
            'conversational' => Sambanova\ChatProvider::class,
            'feature-extraction' => Sambanova\FeatureExtractionProvider::class,
        ],

        // Replicate
        'replicate' => [
            'text-to-image' => Replicate\TextToImageProvider::class,
            'text-to-speech' => Replicate\TextToSpeechProvider::class,
            'automatic-speech-recognition' => Replicate\AutomaticSpeechRecognitionProvider::class,
        ],

        // Cohere
        'cohere' => [
            'conversational' => Cohere\ChatProvider::class,
        ],

        // OpenAI
        'openai' => [
            'conversational' => OpenAI\ChatProvider::class,
        ],

        // ZAI (z.ai)
        'zai-org' => [
            'conversational' => Zai\ChatProvider::class,
            'text-to-image' => Zai\TextToImageProvider::class,
        ],

        // Black Forest Labs (FLUX)
        'black-forest-labs' => [
            'text-to-image' => BlackForestLabs\TextToImageProvider::class,
        ],

        // Novita
        'novita' => [
            'conversational' => Novita\ChatProvider::class,
            'text-generation' => Novita\TextGenerationProvider::class,
        ],

        // Hyperbolic
        'hyperbolic' => [
            'conversational' => Hyperbolic\ChatProvider::class,
            'text-generation' => Hyperbolic\TextGenerationProvider::class,
            'text-to-image' => Hyperbolic\TextToImageProvider::class,
        ],

        // Featherless AI
        'featherless-ai' => [
            'text-generation' => FeatherlessAi\TextGenerationProvider::class,
        ],

        // Scaleway
        'scaleway' => [
            'text-generation' => Scaleway\TextGenerationProvider::class,
            'feature-extraction' => Scaleway\FeatureExtractionProvider::class,
        ],

        // Nscale
        'nscale' => [
            'text-to-image' => Nscale\TextToImageProvider::class,
        ],

        // OVHcloud
        'ovhcloud' => [
            'conversational' => OVHcloud\ChatProvider::class,
            'text-generation' => OVHcloud\TextGenerationProvider::class,
        ],

        // Fal AI
        'fal-ai' => [
            'text-to-image' => FalAi\TextToImageProvider::class,
            'text-to-video' => FalAi\TextToVideoProvider::class,
            'image-to-image' => FalAi\ImageToImageProvider::class,
            'image-to-video' => FalAi\ImageToVideoProvider::class,
            'automatic-speech-recognition' => FalAi\AutomaticSpeechRecognitionProvider::class,
            'text-to-speech' => FalAi\TextToSpeechProvider::class,
        ],
    ];

    /** @var array<string, array<string, ProviderHelperInterface>> Cached instances */
    private static array $instances = [];

    /**
     * Get the appropriate provider helper for a provider and task.
     *
     * @throws RoutingException If the provider/task combination is not supported
     */
    public static function get(InferenceProvider $provider, InferenceTask $task): ProviderHelperInterface
    {
        $providerKey = $provider->value;
        $taskKey = $task->value;

        if (!isset(self::PROVIDERS[$providerKey])) {
            throw new RoutingException(
                "Provider '{$providerKey}' is not supported. Supported providers: "
                .implode(', ', array_keys(self::PROVIDERS))
            );
        }

        if (!isset(self::PROVIDERS[$providerKey][$taskKey])) {
            $supported = array_keys(self::PROVIDERS[$providerKey]);

            throw new RoutingException(
                "Task '{$taskKey}' is not supported for provider '{$providerKey}'. "
                .'Supported tasks: '.implode(', ', $supported)
            );
        }

        // Lazy instantiation with caching
        if (!isset(self::$instances[$providerKey][$taskKey])) {
            $class = self::PROVIDERS[$providerKey][$taskKey];
            self::$instances[$providerKey][$taskKey] = new $class();
        }

        return self::$instances[$providerKey][$taskKey];
    }

    /**
     * Check if a provider/task combination is supported.
     */
    public static function supports(InferenceProvider $provider, InferenceTask $task): bool
    {
        return isset(self::PROVIDERS[$provider->value][$task->value]);
    }

    /**
     * Get all supported providers.
     *
     * @return array<InferenceProvider>
     */
    public static function supportedProviders(): array
    {
        return array_map(
            static fn (string $key) => InferenceProvider::from($key),
            array_keys(self::PROVIDERS)
        );
    }

    /**
     * Get all supported tasks for a provider.
     *
     * @return array<InferenceTask>
     */
    public static function supportedTasks(InferenceProvider $provider): array
    {
        $tasks = self::PROVIDERS[$provider->value] ?? [];

        return array_map(
            static fn (string $key) => InferenceTask::from($key),
            array_keys($tasks)
        );
    }

    /**
     * Reset cached instances (for testing).
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$instances = [];
    }
}
