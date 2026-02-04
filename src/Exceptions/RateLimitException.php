<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Exceptions;

/**
 * Exception thrown when rate limited by the Hub API.
 */
class RateLimitException extends HuggingFaceException
{
    public function __construct(
        string $message,
        public readonly int $retryAfter = 60,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 429, $previous);
    }

    public static function fromHeaders(string $message, ?string $retryAfterHeader): self
    {
        $retryAfter = null !== $retryAfterHeader ? (int) $retryAfterHeader : 60;

        return new self(
            "Rate limited: {$message}. Retry after {$retryAfter} seconds.",
            $retryAfter
        );
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfter;
    }
}
