<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Exceptions;

/**
 * Exception for invalid repository ID format.
 */
class InvalidRepoIdException extends ValidationException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 'repoId', $previous);
    }
}
