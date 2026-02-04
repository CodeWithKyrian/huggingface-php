<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Exceptions;

use Codewithkyrian\HuggingFace\Http\Response;

/**
 * Thrown when an inference provider returns an error response.
 *
 * Contains detailed information about the failed request for debugging.
 */
class ProviderApiException extends InferenceException
{
    /**
     * @param string      $message    Error message
     * @param string      $url        Request URL
     * @param int         $statusCode HTTP status code
     * @param null|string $requestId  Request ID for tracing
     * @param mixed       $body       Response body
     */
    public function __construct(
        string $message,
        public readonly string $url,
        public readonly int $statusCode,
        public readonly ?string $requestId = null,
        public readonly mixed $body = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Create from an HTTP response.
     */
    public static function fromResponse(Response $response, string $url): self
    {
        $body = $response->json();

        // Extract error message from various response formats
        $message = match (true) {
            \is_string($body['error'] ?? null) => $body['error'],
            \is_string($body['error']['message'] ?? null) => $body['error']['message'],
            \is_string($body['message'] ?? null) => $body['message'],
            \is_string($body['detail'] ?? null) => $body['detail'],
            default => 'Inference request failed',
        };

        return new self(
            message: "Inference API error: {$message}",
            url: $url,
            statusCode: $response->status(),
            requestId: $response->header('x-request-id'),
            body: $body,
        );
    }

    /**
     * Create for a chat completion error.
     */
    public static function chatCompletionNotSupported(
        string $provider,
        string $model,
        Response $response,
        string $url
    ): self {
        $body = $response->json();
        $errorDetail = \is_string($body['error'] ?? null) ? $body['error'] : json_encode($body);

        return new self(
            message: "Provider {$provider} does not support chat completion for model {$model}. Error: {$errorDetail}",
            url: $url,
            statusCode: $response->status(),
            requestId: $response->header('x-request-id'),
            body: $body,
        );
    }
}
