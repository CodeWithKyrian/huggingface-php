<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;

/**
 * Contract for provider-specific task helpers.
 *
 * Each provider (HF Inference, Together, Groq, etc.) has different URL patterns,
 * payload formats, and response structures. Provider helpers abstract these differences
 * to provide a unified interface.
 */
interface ProviderHelperInterface
{
    /**
     * Build the request URL for this provider and task.
     *
     * @param string             $model       Model identifier (HF ID or provider-specific)
     * @param AuthMethod         $authMethod  The authentication method being used
     * @param null|InferenceTask $task        The task being performed
     * @param null|string        $endpointUrl Optional custom endpoint URL
     */
    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string;

    /**
     * Build the route path (without base URL).
     *
     * @param string             $model Model identifier
     * @param null|InferenceTask $task  The task being performed
     */
    public function makeRoute(string $model, ?InferenceTask $task = null): string;

    /**
     * Prepare the request payload.
     *
     * @param array<string, mixed> $args  Input parameters
     * @param string               $model Model identifier
     *
     * @return array<string, mixed>
     */
    public function preparePayload(array $args, string $model): array;

    /**
     * Validate and transform the response.
     *
     * @param mixed $response Raw API response
     *
     * @return mixed Processed response
     */
    public function getResponse(mixed $response): mixed;

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
        ?string $billTo = null
    ): array;

    /**
     * Get the provider enum.
     */
    public function provider(): InferenceProvider;

    /**
     * Whether this provider requires client-side routing (direct provider key only).
     */
    public function isClientSideRoutingOnly(): bool;
}
