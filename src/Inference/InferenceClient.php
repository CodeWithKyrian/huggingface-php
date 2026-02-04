<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference;

use Codewithkyrian\HuggingFace\Http\CurlConnector;
use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\Builders\AutomaticSpeechRecognitionBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\ChatCompletionBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\FeatureExtractionBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\FillMaskBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\ImageClassificationBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\ImageToTextBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\ObjectDetectionBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\QuestionAnsweringBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\SentenceSimilarityBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\SummarizationBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\TextClassificationBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\TextGenerationBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\TextToImageBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\TextToSpeechBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\TokenClassificationBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\TranslationBuilder;
use Codewithkyrian\HuggingFace\Inference\Builders\ZeroShotClassificationBuilder;
use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\RoutingException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderRegistry;

/**
 * Client for running model inference on Hugging Face.
 *
 * Supports Hugging Face's Inference API and multiple external providers
 * through a unified, fluent interface.
 *
 * Provider Resolution:
 * - Default is `Auto`, which selects the best available provider for a model
 * - Can specify a specific provider (HfInference, Together, Groq, etc.)
 * - Can provide a custom endpoint URL
 *
 * Token handling:
 * - HF tokens (hf_xxx): Routes through HF router for all providers
 * - Provider keys: Routes directly to provider (for client-side-routing-only providers)
 */
final class InferenceClient
{
    private ?InferenceProvider $provider;
    private ?string $endpointUrl = null;
    private ?string $billTo = null;
    private AuthMethod $authMethod;
    private InferenceRouter $router;

    /**
     * @param string                        $token    The Hugging Face API token (or provider-specific key)
     * @param HttpConnector                 $http     HTTP client
     * @param CurlConnector                 $curl     cURL client for streaming
     * @param null|InferenceProvider|string $provider Provider to use:
     *                                                - null: Auto-select best provider for each model (default)
     *                                                - InferenceProvider enum: Use a specific registered provider
     *                                                - String: Either a provider slug (e.g., 'together') or a custom endpoint URL
     *
     * @throws \InvalidArgumentException If string is neither a valid provider slug nor a URL
     */
    public function __construct(
        private readonly string $token,
        private readonly HttpConnector $http,
        private readonly CurlConnector $curl,
        InferenceProvider|string|null $provider = null,
    ) {
        $this->authMethod = AuthMethod::fromToken($this->token);
        $this->router = new InferenceRouter($this->http);

        if (null === $provider) {
            $this->provider = null;
        } elseif ($provider instanceof InferenceProvider) {
            $this->provider = $provider;
        } else {
            $resolved = InferenceProvider::tryFrom($provider);

            if (null !== $resolved) {
                $this->provider = $resolved;
            } elseif (false !== filter_var($provider, \FILTER_VALIDATE_URL)) {
                $this->provider = InferenceProvider::HfInference;
                $this->endpointUrl = $provider;
            } else {
                throw new \InvalidArgumentException(
                    "Invalid provider: '{$provider}'. Must be a valid InferenceProvider slug "
                    ."(e.g., 'together', 'groq') or a URL."
                );
            }
        }
    }

    /**
     * Set the organization to bill for this request.
     */
    public function billTo(string $orgId): self
    {
        $clone = clone $this;
        $clone->billTo = $orgId;

        return $clone;
    }

    /**
     * Get the current provider (null means auto-routing).
     */
    public function getProvider(): ?InferenceProvider
    {
        return $this->provider;
    }

    /**
     * Get the custom endpoint URL if set.
     */
    public function getEndpointUrl(): ?string
    {
        return $this->endpointUrl;
    }

    /**
     * Get the detected authentication method.
     */
    public function getAuthMethod(): AuthMethod
    {
        return $this->authMethod;
    }

    // ========================================================================
    // Text / NLP Tasks
    // ========================================================================

    /**
     * Start building a chat completion request.
     *
     * @param string $model Model ID (e.g., 'meta-llama/Llama-3.1-8B-Instruct')
     */
    public function chatCompletion(string $model): ChatCompletionBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::Conversational);

        return new ChatCompletionBuilder($this->http, $this->curl, $helper, $model, $url, $headers);
    }

    /**
     * Start building a text generation request.
     *
     * @param string $model Model ID (e.g., 'gpt2')
     */
    public function textGeneration(string $model): TextGenerationBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::TextGeneration);

        return new TextGenerationBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building an embeddings request.
     *
     * @param string $model Model ID (e.g., 'sentence-transformers/all-MiniLM-L6-v2')
     */
    public function featureExtraction(string $model): FeatureExtractionBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::FeatureExtraction);

        return new FeatureExtractionBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building a summarization request.
     *
     * @param string $model Model ID (e.g., 'facebook/bart-large-cnn')
     */
    public function summarization(string $model): SummarizationBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::Summarization);

        return new SummarizationBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building a zero-shot classification request.
     *
     * @param string $model Model ID (e.g., 'facebook/bart-large-mnli')
     */
    public function zeroShotClassification(string $model): ZeroShotClassificationBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::ZeroShotClassification);

        return new ZeroShotClassificationBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building a text classification request.
     *
     * @param string $model Model ID (e.g., 'distilbert-base-uncased-finetuned-sst-2-english')
     */
    public function textClassification(string $model): TextClassificationBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::TextClassification);

        return new TextClassificationBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building a question answering request.
     *
     * @param string $model Model ID (e.g., 'deepset/roberta-base-squad2')
     */
    public function questionAnswering(string $model): QuestionAnsweringBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::QuestionAnswering);

        return new QuestionAnsweringBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building a translation request.
     *
     * @param string $model Model ID (e.g., 'Helsinki-NLP/opus-mt-en-fr')
     */
    public function translation(string $model): TranslationBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::Translation);

        return new TranslationBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building a fill mask request.
     *
     * @param string $model Model ID (e.g., 'bert-base-uncased')
     */
    public function fillMask(string $model): FillMaskBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::FillMask);

        return new FillMaskBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building a sentence similarity request.
     *
     * @param string $model Model ID (e.g., 'sentence-transformers/all-MiniLM-L6-v2')
     */
    public function sentenceSimilarity(string $model): SentenceSimilarityBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::SentenceSimilarity);

        return new SentenceSimilarityBuilder($this->http, $helper, $model, $url, $headers);
    }

    // ========================================================================
    // Vision Tasks
    // ========================================================================

    /**
     * Start building a text-to-image request.
     *
     * @param string $model Model ID (e.g., 'black-forest-labs/FLUX.1-schnell')
     */
    public function textToImage(string $model): TextToImageBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::TextToImage);

        return new TextToImageBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building an image classification request.
     *
     * @param string $model Model ID (e.g., 'google/vit-base-patch16-224')
     */
    public function imageClassification(string $model): ImageClassificationBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::ImageClassification);

        return new ImageClassificationBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building an image-to-text (captioning) request.
     *
     * @param string $model Model ID (e.g., 'Salesforce/blip-image-captioning-base')
     */
    public function imageToText(string $model): ImageToTextBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::ImageToText);

        return new ImageToTextBuilder($this->http, $helper, $model, $url, $headers);
    }

    // ========================================================================
    // Audio Tasks
    // ========================================================================

    /**
     * Start building a text-to-speech request.
     *
     * @param string $model Model ID (e.g., 'espnet/kan-bayashi_ljspeech_vits')
     */
    public function textToSpeech(string $model): TextToSpeechBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::TextToSpeech);

        return new TextToSpeechBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building an automatic speech recognition request.
     *
     * @param string $model Model ID (e.g., 'openai/whisper-large-v3')
     */
    public function automaticSpeechRecognition(string $model): AutomaticSpeechRecognitionBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::AutomaticSpeechRecognition, true);

        return new AutomaticSpeechRecognitionBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building a token classification (NER) request.
     *
     * @param string $model Model ID (e.g., 'dbmdz/bert-large-cased-finetuned-conll03-english')
     */
    public function tokenClassification(string $model): TokenClassificationBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::TokenClassification);

        return new TokenClassificationBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Start building an object detection request.
     *
     * @param string $model Model ID (e.g., 'facebook/detr-resnet-50')
     */
    public function objectDetection(string $model): ObjectDetectionBuilder
    {
        [$helper, $url, $headers] = $this->prepareRequest($model, InferenceTask::ObjectDetection);

        return new ObjectDetectionBuilder($this->http, $helper, $model, $url, $headers);
    }

    /**
     * Resolve the provider for a model and task.
     *
     * - If endpoint URL is set, use HfInference
     * - If provider is set, validate and return it
     * - Otherwise, use getMapping for auto-routing
     *
     * @throws RoutingException If no provider available or auth incompatible
     */
    private function resolveProvider(string $model, InferenceTask $task): InferenceProvider
    {
        if (null !== $this->endpointUrl) {
            return InferenceProvider::HfInference;
        }

        if (InferenceTask::Conversational === $task) {
            return InferenceProvider::Auto;
        }

        $mapping = $this->router->getMapping($model, $task, $this->provider);

        return $mapping->provider;
    }

    /**
     * Prepare URL and headers for a task, with auth validation.
     *
     * @return array{0: ProviderHelperInterface, 1: string, 2: array<string, string>}
     *
     * @throws RoutingException If auth method is incompatible with provider
     */
    private function prepareRequest(string $model, InferenceTask $task, bool $isBinary = false): array
    {
        $resolvedProvider = $this->resolveProvider($model, $task);
        $helper = ProviderRegistry::get($resolvedProvider, $task);

        if ($helper->isClientSideRoutingOnly() && AuthMethod::HfToken === $this->authMethod) {
            throw new RoutingException(
                "Provider {$resolvedProvider->value} is closed-source and does not support HF tokens. "
                .'You must use a provider-specific API key instead.'
            );
        }

        $url = $helper->makeUrl($model, $this->authMethod, $task, $this->endpointUrl);
        $headers = $helper->prepareHeaders($this->authMethod, $this->token, $isBinary, $this->billTo);

        return [$helper, $url, $headers];
    }
}
