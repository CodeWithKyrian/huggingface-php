<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Exceptions;

/**
 * Exception for network/connection errors.
 */
class NetworkException extends HuggingFaceException
{
    public static function connectionFailed(string $url, ?\Throwable $previous = null): self
    {
        return new self("Failed to connect to: {$url}", 0, $previous);
    }

    public static function timeout(string $url, int $timeoutSeconds): self
    {
        return new self("Request to {$url} timed out after {$timeoutSeconds} seconds");
    }

    public static function sslError(string $message, ?\Throwable $previous = null): self
    {
        return new self("SSL/TLS error: {$message}", 0, $previous);
    }
}
