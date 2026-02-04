<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace;

use Codewithkyrian\HuggingFace\Support\TokenResolver;

/**
 * Factory builder for creating HuggingFace client instances.
 *
 * Provides a fluent API for configuring the client before instantiation.
 */
final class HuggingFaceFactory
{
    private ?string $token = null;
    private ?string $cacheDir = null;
    private string $hubUrl = 'https://huggingface.co';

    /**
     * Set the authentication token.
     *
     * @param null|string $token The Hugging Face API token
     */
    public function withToken(?string $token): self
    {
        $clone = clone $this;
        $clone->token = $token;

        return $clone;
    }

    /**
     * Set the cache directory.
     *
     * @param null|string $cacheDir Path to cache directory
     */
    public function withCacheDir(?string $cacheDir): self
    {
        $clone = clone $this;
        $clone->cacheDir = $cacheDir;

        return $clone;
    }

    /**
     * Set the Hub API endpoint.
     *
     * @param string $hubUrl The Hub API base URL
     */
    public function withHubUrl(string $hubUrl): self
    {
        $clone = clone $this;
        $clone->hubUrl = rtrim($hubUrl, '/');

        return $clone;
    }

    /**
     * Create the HuggingFace client instance.
     */
    public function make(): HuggingFace
    {
        $token = $this->token ?? TokenResolver::resolve();

        return new HuggingFace($token, $this->cacheDir, $this->hubUrl);
    }
}
