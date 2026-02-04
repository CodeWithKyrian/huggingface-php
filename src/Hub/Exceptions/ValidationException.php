<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Exceptions;

/**
 * Exception for validation errors.
 */
class ValidationException extends HubException
{
    public function __construct(
        string $message,
        public readonly ?string $field = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function required(string $field): self
    {
        return new self("The '{$field}' field is required", $field);
    }

    public static function invalidFormat(string $field, string $expectedFormat): self
    {
        return new self(
            "The '{$field}' field has an invalid format. Expected: {$expectedFormat}",
            $field
        );
    }

    public static function outOfRange(string $field, mixed $min, mixed $max): self
    {
        return new self(
            "The '{$field}' field must be between {$min} and {$max}",
            $field
        );
    }
}
