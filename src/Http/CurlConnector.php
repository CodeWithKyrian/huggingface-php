<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Http;

use Codewithkyrian\HuggingFace\Exceptions\AuthenticationException;
use Codewithkyrian\HuggingFace\Exceptions\NetworkException;
use Codewithkyrian\HuggingFace\Exceptions\RateLimitException;
use Codewithkyrian\HuggingFace\Hub\Exceptions\ApiException;
use Codewithkyrian\HuggingFace\Hub\Exceptions\NotFoundException;
use Codewithkyrian\HuggingFace\Support\Utils;

/**
 * cURL-based HTTP connector for operations requiring streaming or progress callbacks.
 *
 * Use this for:
 * - File downloads with progress tracking and resume support
 * - SSE (Server-Sent Events) streaming for real-time AI responses
 *
 * For standard request/response operations, use HttpConnector (PSR-18 based).
 */
final class CurlConnector
{
    public function __construct(private readonly ?string $token = null) {}

    /**
     * Download a file to the specified destination with progress tracking.
     *
     * @param string                        $url         Source URL
     * @param string                        $destination Local file path
     * @param int                           $totalSize   Total expected file size in bytes
     * @param int                           $offset      Byte offset to start download from (for resume)
     * @param null|callable(int, int): void $onProgress  Progress callback (downloaded, total)
     * @param array<string, string>         $headers     Additional headers
     */
    public function download(
        string $url,
        string $destination,
        int $totalSize,
        int $offset = 0,
        ?callable $onProgress = null,
        array $headers = [],
    ): void {
        Utils::ensureDirectory(\dirname($destination));

        $fp = null;
        $ch = null;
        $attempt = 0;
        $maxRetries = 3;

        while ($attempt <= $maxRetries) {
            try {
                $ch = curl_init($url);
                if (false === $ch) {
                    throw new \RuntimeException('Failed to initialize cURL.');
                }

                $fp = fopen($destination, 'w');
                if (false === $fp) {
                    throw new \RuntimeException("Cannot open file for writing: {$destination}");
                }

                curl_setopt_array($ch, [
                    \CURLOPT_FILE => $fp,
                    \CURLOPT_FOLLOWLOCATION => true,
                    \CURLOPT_MAXREDIRS => 5,
                    \CURLOPT_TIMEOUT => 0,
                    \CURLOPT_CONNECTTIMEOUT => 10,
                    \CURLOPT_FAILONERROR => false,
                    \CURLOPT_NOPROGRESS => null === $onProgress,
                    \CURLOPT_HEADER => false,
                    \CURLOPT_HTTPHEADER => $this->prepareHeaders($headers),
                ]);

                if ($offset > 0) {
                    curl_setopt($ch, \CURLOPT_RESUME_FROM, $offset);
                }

                if (null !== $onProgress) {
                    curl_setopt($ch, \CURLOPT_PROGRESSFUNCTION, static function ($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($onProgress, $offset, $totalSize) {
                        if ($totalSize > 0) {
                            $onProgress($offset + $downloaded, $totalSize);
                        }
                    });
                }

                $success = curl_exec($ch);

                if (false === $success) {
                    throw new NetworkException(curl_error($ch), curl_errno($ch));
                }

                $statusCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);

                if ($statusCode >= 400) {
                    $this->handleHttpError($statusCode, $url);
                }

                break;
            } catch (NetworkException $e) {
                ++$attempt;
                if ($attempt > $maxRetries) {
                    throw $e;
                }
                usleep((int) (2 ** $attempt * 500000));
            } finally {
                if (\is_resource($ch) || $ch instanceof \CurlHandle) {
                    curl_close($ch);
                }
                if (\is_resource($fp)) {
                    fclose($fp);
                }
            }
        }
    }

    /**
     * Stream data from a URL in real-time using cURL multi.
     *
     * Uses cURL multi_exec for non-blocking streaming, yielding chunks
     * as they arrive from the server.
     *
     * @param string                      $url     Target URL
     * @param array<string, mixed>|string $body    Request body (will be JSON encoded if array)
     * @param array<string, string>       $headers Additional headers
     */
    public function stream(
        string $url,
        array|string $body,
        array $headers = [],
    ): StreamResponse {
        $headers['Accept'] = 'text/event-stream';
        $headers['Cache-Control'] = 'no-cache';

        if (\is_array($body)) {
            $body = json_encode($body);
            $headers['Content-Type'] = 'application/json';
        }

        return new StreamResponse($this->streamChunks($url, $body, $headers));
    }

    /**
     * Stream SSE events from a URL in real-time.
     *
     * Combines streaming with SSE parsing for a convenient event-based API.
     *
     * @param string                      $url     Target URL
     * @param array<string, mixed>|string $body    Request body
     * @param array<string, string>       $headers Additional headers
     *
     * @return EventStreamResponse SSE stream with events() generator
     */
    public function streamEvents(
        string $url,
        array|string $body,
        array $headers = [],
    ): EventStreamResponse {
        $headers['Accept'] = 'text/event-stream';
        $headers['Cache-Control'] = 'no-cache';

        if (\is_array($body)) {
            $body = json_encode($body);
            $headers['Content-Type'] = 'application/json';
        }

        return new EventStreamResponse($this->streamChunks($url, $body, $headers));
    }

    /**
     * Internal: Stream raw chunks from URL using cURL multi.
     *
     * @param array<string, string> $headers
     *
     * @return \Generator<string>
     */
    private function streamChunks(
        string $url,
        string $body,
        array $headers,
    ): \Generator {
        $curlHeaders = $this->prepareHeaders($headers);

        $ch = curl_init($url);

        if (false === $ch) {
            throw new \RuntimeException("Failed to initialize cURL for URL: {$url}");
        }

        /** @var \ArrayObject<int, string> */
        $chunks = new \ArrayObject();

        curl_setopt_array($ch, [
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => $body,
            \CURLOPT_HTTPHEADER => $curlHeaders,
            \CURLOPT_RETURNTRANSFER => false,
            \CURLOPT_FOLLOWLOCATION => true,
            \CURLOPT_MAXREDIRS => 5,
            \CURLOPT_WRITEFUNCTION => static function ($curl, $data) use ($chunks) {
                $chunks->append($data);

                return \strlen($data);
            },
        ]);

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);

        do {
            $status = curl_multi_exec($mh, $active);

            if ($chunks->count() > 0) {
                foreach ($chunks->exchangeArray([]) as $chunk) {
                    yield $chunk;
                }
            }

            if ($active && \CURLM_OK === $status) {
                curl_multi_select($mh, 0.1);
            }
        } while ($active && \CURLM_OK === $status);

        if ($chunks->count() > 0) {
            foreach ($chunks->exchangeArray([]) as $chunk) {
                yield $chunk;
            }
        }

        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);

        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
        curl_close($ch);

        if ('' !== $error) {
            throw new NetworkException("cURL error during streaming: {$error}");
        }

        if ($httpCode >= 400) {
            // Note: chunks are already yielded/cleared, so response body reconstruction is not possible here
            // from the buffer. However, the original code had a bug: $chunks was being cleared (via shift)
            // so `implode('', $chunks)` on line 240 would strictly be empty anyway if the previous loops ran.
            // But if there were leftover chunks, they are gone now.
            // Let's acknowledge the limit of streaming: we can't reconstruct the full body if we streamed it.
            // But the original code was: yield all chunks -> then check error.
            // If we are streaming, we yielded the error body already potentially.
            // We'll pass an empty string or partial string if we want to be safe, but adhering to the original logic
            // implies $chunks might have been empty too.
            // Actually, wait: The original code logic was:
            // yield array_shift($chunks) in the loop.
            // Then outside loop: yield remaining chunks.
            // Then check error.
            // Then `implode('', $chunks)`.
            // IMPLODE WOULD BE EMPTY because the chunks were shifted off!
            // So the original code passed "" to handleHttpError if everything was yielded.
            // I will pass "" here to match the behavior (or rather the necessary consequence).
            $responseBody = '';

            $this->handleHttpError($httpCode, $responseBody);
        }
    }

    /**
     * Handle HTTP error responses with proper exceptions.
     */
    private function handleHttpError(int $status, string $body): void
    {
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
            409 => throw new ApiException("Conflict: {$message}", $status, \is_array($data) ? $data : null),
            429 => throw RateLimitException::fromHeaders($message, null),
            default => match (true) {
                $status >= 500 => throw new ApiException("Server error: {$message}", $status, \is_array($data) ? $data : null),
                $status >= 400 => throw new ApiException($message, $status, \is_array($data) ? $data : null),
                default => null,
            }
        };
    }

    /**
     * Prepare headers array for cURL.
     *
     * @param array<string, string> $headers
     *
     * @return string[]
     */
    private function prepareHeaders(array $headers): array
    {
        $curlHeaders = [];

        // Add auth header if token is set
        if (null !== $this->token && '' !== $this->token) {
            $curlHeaders[] = "Authorization: Bearer {$this->token}";
        }

        // Add user agent
        $curlHeaders[] = 'User-Agent: '.Utils::userAgent();

        // Add custom headers
        foreach ($headers as $name => $value) {
            $curlHeaders[] = "{$name}: {$value}";
        }

        return $curlHeaders;
    }
}
