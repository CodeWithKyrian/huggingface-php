<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Exceptions;

/**
 * Base exception for all Hugging Face library exceptions.
 */
class HuggingFaceException extends \Exception
{
    /**
     * Create a new exception with additional context.
     *
     * @param array<string, mixed> $context
     */
    public static function withContext(string $message, array $context = [], ?\Throwable $previous = null): self
    {
        if (!empty($context)) {
            $contextStr = json_encode($context, \JSON_UNESCAPED_SLASHES);
            $message .= " Context: {$contextStr}";
        }

        return new self($message, 0, $previous);
    }
}
