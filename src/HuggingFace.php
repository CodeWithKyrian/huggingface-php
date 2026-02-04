<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace;

use Codewithkyrian\HuggingFace\Http\CurlConnector;
use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\HubClient;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\InferenceClient;
use Codewithkyrian\HuggingFace\Support\CacheManager;

/**
 * Main entry point for the Hugging Face PHP library.
 *
 * Provides access to the Hub API for repository management, file operations,
 * and model inference.
 */
final class HuggingFace
{
    public const VERSION = '1.0.0';

    /**
     * Create a new HuggingFace instance.
     *
     * @internal use HuggingFace::client() or HuggingFace::factory() instead
     */
    public function __construct(
        public readonly ?string $token = null,
        public readonly ?string $cacheDir = null,
        public readonly string $hubUrl = 'https://huggingface.co',
    ) {}

    /**
     * Create a client with a token (or auto-detect from environment).
     *
     * @param null|string $token The Hugging Face API token (optional, will try to auto-detect)
     */
    public static function client(?string $token = null): self
    {
        return self::factory()
            ->withToken($token)
            ->make();
    }

    /**
     * Get a factory builder for full configuration.
     */
    public static function factory(): HuggingFaceFactory
    {
        return new HuggingFaceFactory();
    }

    /**
     * Get the Hub API client.
     *
     * Use this to access repository management, file operations, search, etc.
     */
    public function hub(): HubClient
    {
        $http = new HttpConnector($this->token);
        $curl = new CurlConnector($this->token);
        $cache = new CacheManager($this->cacheDir);

        return new HubClient($this->hubUrl, $http, $curl, $cache);
    }

    /**
     * Get the Inference API client.
     *
     * Use this to run model inference for text generation, embeddings,
     * image classification, and more.
     *
     * @param null|InferenceProvider|string $provider The inference provider to use.
     *                                                - null: Auto-select best provider for each model (default)
     *                                                - InferenceProvider enum: Use a specific registered provider
     *                                                - String: Either a provider slug (e.g., 'together') or a custom endpoint URL
     *
     * @throws \InvalidArgumentException If the string is neither a valid provider slug nor a URL
     */
    public function inference(InferenceProvider|string|null $provider = null): InferenceClient
    {
        $http = new HttpConnector($this->token);
        $curl = new CurlConnector($this->token);

        return new InferenceClient($this->token, $http, $curl, $provider);
    }

    /**
     * Access the cache management utilities.
     */
    public function cache(): Cache
    {
        $cache = new CacheManager($this->cacheDir);

        return new Cache($cache);
    }
}
