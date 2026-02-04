<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Exceptions;

/**
 * Exception for authentication-related errors.
 */
class AuthenticationException extends HuggingFaceException
{
    public static function invalidToken(string $reason = 'Invalid or expired token'): self
    {
        return new self("Authentication failed: {$reason}");
    }

    public static function missingToken(): self
    {
        return new self(
            'No authentication token provided. Set the HF_TOKEN environment variable, '
            .'pass a token to the client constructor, or login via the Hugging Face CLI.'
        );
    }

    public static function insufficientPermissions(string $action, ?string $resource = null): self
    {
        $message = "Insufficient permissions to {$action}";
        if (null !== $resource) {
            $message .= " on '{$resource}'";
        }

        return new self($message);
    }
}
