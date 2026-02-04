<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Base class for provider-specific task helpers.
 *
 * Each provider (HF Inference, Together, Groq, etc.) has different URL patterns,
 * payload formats, and response structures. Provider helpers abstract these differences
 * to provide a unified interface.
 *
 * @internal
 */
abstract class ProviderHelper implements ProviderHelperInterface
{
    /**
     * HF Router URL for proxying requests to external providers.
     */
    protected const HF_ROUTER_URL = 'https://router.huggingface.co';

    public function __construct(
        protected readonly InferenceProvider $provider,
        protected readonly string $baseUrl,
        protected readonly bool $clientSideRoutingOnly = false,
    ) {}

    /**
     * Build the request URL for this provider and task.
     */
    abstract public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string;

    /**
     * Build the route path (without base URL).
     */
    abstract public function makeRoute(string $model, ?InferenceTask $task = null): string;

    /**
     * Prepare the request payload.
     *
     * @param array<string, mixed> $args  Input parameters
     * @param string               $model Model identifier
     *
     * @return array<string, mixed>
     */
    abstract public function preparePayload(array $args, string $model): array;

    /**
     * Validate and transform the response.
     *
     * @param mixed $response Raw API response
     *
     * @return mixed Processed response
     *
     * @throws OutputValidationException
     */
    abstract public function getResponse(mixed $response): mixed;

    /**
     * Prepare request headers.
     *
     * @param AuthMethod  $authMethod  The authentication method
     * @param null|string $accessToken Authentication token
     * @param bool        $isBinary    Whether the request body is binary data
     * @param null|string $billTo      Organization to bill
     *
     * @return array<string, string>
     */
    public function prepareHeaders(
        AuthMethod $authMethod,
        ?string $accessToken = null,
        bool $isBinary = false,
        ?string $billTo = null,
    ): array {
        $headers = [];

        if (!$isBinary) {
            $headers['Content-Type'] = 'application/json';
        }

        if (null !== $billTo && '' !== $billTo) {
            $headers['X-HF-Bill-To'] = $billTo;
        }

        return $headers;
    }

    /**
     * Get the provider enum.
     */
    public function provider(): InferenceProvider
    {
        return $this->provider;
    }

    /**
     * Whether this provider requires client-side routing (direct provider key only).
     */
    public function isClientSideRoutingOnly(): bool
    {
        return $this->clientSideRoutingOnly;
    }

    /**
     * Build the base URL based on auth method.
     *
     * When using an HF token, routes through HF router.
     * When using a provider key, goes directly to provider.
     */
    protected function makeBaseUrl(AuthMethod $authMethod, ?string $endpointUrl = null): string
    {
        // Custom endpoint always takes precedence
        if (null !== $endpointUrl) {
            return rtrim($endpointUrl, '/');
        }

        // HF token routes through HF router for external providers
        if (AuthMethod::ProviderKey !== $authMethod) {
            return self::HF_ROUTER_URL.'/'.$this->provider->value;
        }

        // Provider key goes directly to provider
        return $this->baseUrl;
    }
}
