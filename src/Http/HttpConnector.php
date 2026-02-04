<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Http;

use Codewithkyrian\HuggingFace\Exceptions\AuthenticationException;
use Codewithkyrian\HuggingFace\Exceptions\NetworkException;
use Codewithkyrian\HuggingFace\Exceptions\RateLimitException;
use Codewithkyrian\HuggingFace\Hub\Exceptions\ApiException;
use Codewithkyrian\HuggingFace\Hub\Exceptions\NotFoundException;
use Codewithkyrian\HuggingFace\Support\Utils;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * HTTP connector using PSR-18 HTTP Client.
 *
 * Wraps HTTP requests with authentication, error handling, and redirects.
 */
final class HttpConnector
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private UriFactoryInterface $uriFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(private readonly ?string $token = null)
    {
        $this->client = Psr18ClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->uriFactory = Psr17FactoryDiscovery::findUriFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Send a GET request.
     *
     * @param array<string, mixed>  $query
     * @param array<string, string> $headers
     */
    public function get(string $url, array $query = [], array $headers = []): Response
    {
        $url = Utils::buildUrl($url, $query);

        return $this->request('GET', $url, null, $headers);
    }

    /**
     * Send a HEAD request.
     *
     * @param array<string, mixed>  $query
     * @param array<string, string> $headers
     */
    public function head(string $url, array $query = [], array $headers = []): Response
    {
        $url = Utils::buildUrl($url, $query);

        return $this->request('HEAD', $url, null, $headers);
    }

    /**
     * Send a POST request.
     *
     * @param null|array<string, mixed>|resource|string $body
     * @param array<string, string>                     $headers
     */
    public function post(string $url, mixed $body = null, array $headers = []): Response
    {
        return $this->request('POST', $url, $body, $headers);
    }

    /**
     * Send a PUT request.
     *
     * @param null|array<string, mixed>|resource|string $body
     * @param array<string, string>                     $headers
     */
    public function put(string $url, mixed $body = null, array $headers = []): Response
    {
        return $this->request('PUT', $url, $body, $headers);
    }

    /**
     * Send a DELETE request.
     *
     * @param array<string, string> $headers
     */
    public function delete(string $url, mixed $body = null, array $headers = []): Response
    {
        return $this->request('DELETE', $url, $body, $headers);
    }

    /**
     * Send a PATCH request.
     *
     * @param null|array<string, mixed>|resource|string $body
     * @param array<string, string>                     $headers
     */
    public function patch(string $url, mixed $body = null, array $headers = []): Response
    {
        return $this->request('PATCH', $url, $body, $headers);
    }

    /**
     * Send a request and handle response.
     *
     * @param null|array<string, mixed>|resource|string $body
     * @param array<string, string>                     $headers
     */
    private function request(
        string $method,
        string $url,
        mixed $body = null,
        array $headers = []
    ): Response {
        $response = $this->sendRequest($method, $url, $body, $headers);

        return new Response($response);
    }

    /**
     * Build and send the HTTP request with retry logic.
     *
     * @param null|array<string, mixed>|string $body
     * @param array<string, string>            $headers
     */
    private function sendRequest(
        string $method,
        string $url,
        mixed $body,
        array $headers
    ): ResponseInterface {
        $request = $this->buildRequest($method, $url, $body, $headers);
        $maxRetries = 3;
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            ++$attempt;

            try {
                $response = $this->sendWithRedirects($request);
                $status = $response->getStatusCode();

                // Retry on server errors (5xx) and 429 rate limit
                if ($status >= 500 || 429 === $status) {
                    if ($attempt < $maxRetries) {
                        $retryAfter = $this->getRetryDelay($response, $attempt);
                        usleep($retryAfter * 1000); // Convert to microseconds

                        continue;
                    }
                }

                $this->handleErrors($response, $url);

                return $response;
            } catch (ClientExceptionInterface $e) {
                $lastException = $e;

                // Retry on network errors
                if ($attempt < $maxRetries) {
                    usleep($this->calculateBackoff($attempt) * 1000);

                    continue;
                }

                throw NetworkException::connectionFailed($url, $e);
            }
        }

        throw $lastException ?? NetworkException::connectionFailed($url);
    }

    /**
     * Calculate retry delay from response or default backoff.
     */
    private function getRetryDelay(ResponseInterface $response, int $attempt): int
    {
        $retryAfter = $response->getHeaderLine('Retry-After');

        if ('' !== $retryAfter) {
            // Retry-After can be seconds or HTTP-date
            if (is_numeric($retryAfter)) {
                return (int) $retryAfter * 1000; // Convert to ms
            }

            $date = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC7231, $retryAfter);
            if ($date) {
                return max(0, ($date->getTimestamp() - time()) * 1000);
            }
        }

        return $this->calculateBackoff($attempt);
    }

    /**
     * Calculate exponential backoff delay in milliseconds.
     */
    private function calculateBackoff(int $attempt): int
    {
        // Base delay of 500ms with exponential backoff and jitter
        $baseDelay = 500;
        $maxDelay = 10000; // 10 seconds max

        $delay = min($baseDelay * (2 ** ($attempt - 1)), $maxDelay);

        // Add jitter (±25%)
        $jitter = $delay * 0.25;
        $delay += random_int((int) -$jitter, (int) $jitter);

        return max(100, $delay);
    }

    /**
     * Build a PSR-7 request.
     *
     * @param null|array<string, mixed>|string $body
     * @param array<string, string>            $headers
     */
    private function buildRequest(
        string $method,
        string $url,
        array|string|null $body,
        array $headers
    ): RequestInterface {
        $request = $this->requestFactory->createRequest($method, $url);

        if (null !== $this->token && !isset($headers['Authorization'])) {
            $request = $request->withHeader('Authorization', "Bearer {$this->token}");
        }

        $request = $request
            ->withHeader('User-Agent', Utils::userAgent())
            ->withHeader('Accept', 'application/json');

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (null !== $body) {
            if (\is_array($body)) {
                $request = $request
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($body)));
            } elseif (\is_resource($body)) {
                $request = $request->withBody($this->streamFactory->createStreamFromResource($body));
            } else {
                $request = $request->withBody($this->streamFactory->createStream($body));
            }
        }

        return $request;
    }

    /**
     * Send request with automatic redirect following.
     */
    private function sendWithRedirects(RequestInterface $request, int $maxRedirects = 5): ResponseInterface
    {
        $redirectCount = 0;
        $currentRequest = $request;

        while ($redirectCount < $maxRedirects) {
            $response = $this->client->sendRequest($currentRequest);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 300 && $statusCode < 400) {
                $location = $response->getHeaderLine('Location');

                if ('' === $location) {
                    throw new NetworkException('Received redirect response without Location header');
                }

                $currentRequest = $this->buildRedirectRequest($currentRequest, $location);
                ++$redirectCount;

                continue;
            }

            return $response;
        }

        throw new NetworkException("Too many redirects (max: {$maxRedirects})");
    }

    /**
     * Build a new request for following a redirect.
     */
    private function buildRedirectRequest(RequestInterface $originalRequest, string $location): RequestInterface
    {
        // Handle absolute vs relative URLs
        if (preg_match('/^https?:\/\//', $location)) {
            $uri = $this->uriFactory->createUri($location);
        } else {
            // Relative URL
            $originalUri = $originalRequest->getUri();
            $parsed = parse_url($location);

            if (str_starts_with($location, '/')) {
                // Absolute path
                $uri = $originalUri
                    ->withPath($parsed['path'] ?? '/')
                    ->withQuery($parsed['query'] ?? '')
                    ->withFragment($parsed['fragment'] ?? '');
            } else {
                // Relative path
                $basePath = \dirname($originalUri->getPath());
                $uri = $originalUri
                    ->withPath(rtrim($basePath, '/').'/'.($parsed['path'] ?? ''))
                    ->withQuery($parsed['query'] ?? '')
                    ->withFragment($parsed['fragment'] ?? '');
            }
        }

        $request = $this->requestFactory->createRequest('GET', $uri)
            ->withHeader('User-Agent', Utils::userAgent());

        // Preserve all original headers (like Range) except Host and Authorization
        foreach ($originalRequest->getHeaders() as $name => $values) {
            if (0 === strcasecmp($name, 'Host') || 0 === strcasecmp($name, 'Authorization')) {
                continue;
            }
            foreach ($values as $value) {
                $request = $request->withHeader($name, $value);
            }
        }

        // Preserve authorization for same-host redirects
        $originalHost = $originalRequest->getUri()->getHost();
        $newHost = $uri->getHost();

        if ($originalHost === $newHost && null !== $this->token) {
            $request = $request->withHeader('Authorization', "Bearer {$this->token}");
        }

        return $request;
    }

    /**
     * Handle HTTP error responses.
     */
    private function handleErrors(ResponseInterface $response, string $url): void
    {
        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return;
        }

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $message = $body;
        if (\is_array($data)) {
            if (isset($data['error']['message']) && \is_string($data['error']['message'])) {
                $message = $data['error']['message'];
            } elseif (isset($data['error']) && \is_string($data['error'])) {
                $message = $data['error'];
            } elseif (isset($data['message']) && \is_string($data['message'])) {
                $message = $data['message'];
            }
        }

        match ($status) {
            401 => throw AuthenticationException::invalidToken($message),
            403 => throw AuthenticationException::insufficientPermissions($message),
            404 => throw new NotFoundException($message),
            409 => throw new ApiException("Conflict: {$message}", $status, $data),
            429 => throw RateLimitException::fromHeaders($message, $response->getHeaderLine('Retry-After') ?: null),
            default => match (true) {
                $status >= 500 => throw new ApiException("Server error: {$message}", $status, $data),
                $status >= 400 => throw new ApiException($message, $status, $data),
                default => null,
            }
        };
    }
}
