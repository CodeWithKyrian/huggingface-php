<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Exceptions;

/**
 * Thrown when a provider's response doesn't match the expected format.
 *
 * This indicates a mismatch between what the provider returned and what
 * the library expected, possibly due to API changes or unsupported models.
 */
class OutputValidationException extends InferenceException
{
    /**
     * @param string $message  Description of what was expected vs received
     * @param mixed  $received The actual response data
     */
    public function __construct(
        string $message,
        public readonly mixed $received = null,
    ) {
        parent::__construct($message);
    }
}
