<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Exceptions;

/**
 * Exception thrown when a resource is not found.
 */
class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Resource not found', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, null, $previous);
    }

    public static function repository(string $repoId): self
    {
        return new self("Repository '{$repoId}' not found or you don't have access to it");
    }

    public static function file(string $repoId, string $filename): self
    {
        return new self("File '{$filename}' not found in repository '{$repoId}'");
    }

    public static function revision(string $repoId, string $revision): self
    {
        return new self("Revision '{$revision}' not found in repository '{$repoId}'");
    }

    public static function model(string $modelId): self
    {
        return new self("Model '{$modelId}' not found or not accessible");
    }
}
