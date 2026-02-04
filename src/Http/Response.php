<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Wrapper around PSR-7 ResponseInterface for convenient access.
 */
final class Response
{
    /** @var null|array<string, mixed> */
    private ?array $decodedJson = null;
    private ?string $bodyContents = null;

    public function __construct(
        private readonly ResponseInterface $response
    ) {}

    /**
     * Get the HTTP status code.
     */
    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * Check if the response is successful (2xx).
     */
    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Check if the response is a redirect (3xx).
     */
    public function redirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Check if the response is a client error (4xx).
     */
    public function clientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Check if the response is a server error (5xx).
     */
    public function serverError(): bool
    {
        return $this->status() >= 500;
    }

    /**
     * Get a specific header value.
     */
    public function header(string $name): ?string
    {
        $value = $this->response->getHeaderLine($name);

        return '' !== $value ? $value : null;
    }

    /**
     * Get all headers.
     *
     * @return array<string, string[]>
     */
    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Get the response body as a string.
     */
    public function body(): string
    {
        if (null === $this->bodyContents) {
            $this->bodyContents = (string) $this->response->getBody();
        }

        return $this->bodyContents;
    }

    /**
     * Get the response body decoded as JSON.
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if (null === $this->decodedJson) {
            $this->decodedJson = json_decode($this->body(), true) ?? [];
        }

        return $this->decodedJson;
    }

    /**
     * Get a specific value from the JSON response.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->json()[$key] ?? $default;
    }

    /**
     * Get the underlying PSR-7 response.
     */
    public function toPsrResponse(): ResponseInterface
    {
        return $this->response;
    }
}
