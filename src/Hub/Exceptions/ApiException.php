<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Exceptions;

/**
 * Exception for Hub API errors.
 */
class ApiException extends HubException
{
    /**
     * @param null|array<string, mixed> $response
     */
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?array $response = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(int $statusCode, string $body): self
    {
        $data = json_decode($body, true);
        $message = $data['error'] ?? $data['message'] ?? $body;

        return new self($message, $statusCode, $data);
    }
}
