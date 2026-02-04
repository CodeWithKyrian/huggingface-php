<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Exceptions;

/**
 * Exception for invalid revision references.
 */
class InvalidRevisionException extends ValidationException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 'revision', $previous);
    }

    public static function invalid(string $revision): self
    {
        return new self(
            "Invalid revision format: '{$revision}'. "
            ."Valid formats include branch names (e.g., 'main'), tags, or commit SHAs."
        );
    }
}
