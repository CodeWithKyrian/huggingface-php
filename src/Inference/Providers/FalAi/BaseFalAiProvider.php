<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\FalAi;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelper;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

/**
 * Base provider for FalAi tasks.
 *
 * FalAi uses a queue-based async architecture where many tasks return a request_id
 * that must be polled for completion before retrieving results.
 *
 * @see https://fal.ai/docs
 */
abstract class BaseFalAiProvider extends ProviderHelper
{
    protected const BASE_URL = 'https://fal.run';
    protected const QUEUE_URL = 'https://queue.fal.run';

    protected bool $useQueue = false;
    protected ?ClientInterface $httpClient = null;

    public function __construct()
    {
        parent::__construct(InferenceProvider::FalAi, static::BASE_URL);
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return $model;
    }

    public function makeUrl(
        string $model,
        AuthMethod $authMethod,
        ?InferenceTask $task = null,
        ?string $endpointUrl = null
    ): string {
        $base = $this->makeBaseUrl($authMethod, $endpointUrl);

        // For queue-based tasks, use queue subdomain
        if ($this->useQueue && AuthMethod::ProviderKey !== $authMethod) {
            $base = str_replace('fal.run', 'queue.fal.run', $base);
        }

        $route = $this->makeRoute($model, $task);

        // Add queue subdomain parameter when using HF token
        if ($this->useQueue && AuthMethod::ProviderKey !== $authMethod) {
            return "{$base}/{$route}?_subdomain=queue";
        }

        return "{$base}/{$route}";
    }

    /**
     * FalAi uses different auth header format for provider keys.
     */
    public function prepareHeaders(
        AuthMethod $authMethod,
        ?string $accessToken = null,
        bool $isBinary = false,
        ?string $billTo = null,
    ): array {
        $headers = parent::prepareHeaders($authMethod, $accessToken, $isBinary, $billTo);

        if (AuthMethod::ProviderKey === $authMethod && $accessToken) {
            $headers['Authorization'] = "Key {$accessToken}";
        }

        return $headers;
    }

    /**
     * Poll the queue for task completion and retrieve result.
     *
     * @param array{request_id: string, status: string, response_url: string} $queueResponse
     * @param string                                                          $url           The original request URL
     * @param array<string, string>                                           $headers       Request headers for polling
     * @param ClientInterface                                                 $httpClient    HTTP client for polling requests
     *
     * @return array<string, mixed> The final result
     */
    protected function pollQueueForResult(
        array $queueResponse,
        string $url,
        array $headers,
        ClientInterface $httpClient
    ): array {
        $requestId = $queueResponse['request_id'] ?? null;
        if (!$requestId) {
            throw new OutputValidationException(
                'No request_id found in Fal.ai queue response',
                $queueResponse
            );
        }

        $status = $queueResponse['status'] ?? '';
        $responseUrl = $queueResponse['response_url'] ?? '';

        // Extract base URL and model path from response_url
        $parsedUrl = parse_url($url);
        $parsedResponseUrl = parse_url($responseUrl);
        $modelPath = $parsedResponseUrl['path'] ?? '';
        $queryParams = $parsedUrl['query'] ?? '';

        $baseUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";

        // If using HF router, prepend provider path
        if (str_contains($parsedUrl['host'] ?? '', 'router.huggingface.co')) {
            $baseUrl .= '/fal-ai';
        }

        $statusUrl = "{$baseUrl}{$modelPath}/status".($queryParams ? "?{$queryParams}" : '');
        $resultUrl = "{$baseUrl}{$modelPath}".($queryParams ? "?{$queryParams}" : '');

        // Poll until completed
        while ('COMPLETED' !== $status) {
            usleep(500000); // 500ms delay

            $statusRequest = new Request('GET', $statusUrl, $headers);
            $statusResponse = $httpClient->sendRequest($statusRequest);

            if ($statusResponse->getStatusCode() >= 400) {
                throw new ProviderApiException(
                    'Failed to fetch status from Fal.ai queue API',
                    $statusUrl,
                    $statusResponse->getStatusCode(),
                    $statusResponse->getBody()->getContents()
                );
            }

            $statusData = json_decode($statusResponse->getBody()->getContents(), true);
            $status = $statusData['status'] ?? '';

            if ('FAILED' === $status) {
                throw new OutputValidationException(
                    'Fal.ai task failed',
                    $statusData
                );
            }
        }

        // Fetch final result
        $resultRequest = new Request('GET', $resultUrl, $headers);
        $resultResponse = $httpClient->sendRequest($resultRequest);

        return json_decode($resultResponse->getBody()->getContents(), true);
    }
}
