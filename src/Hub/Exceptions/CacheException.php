<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Exceptions;

/**
 * Exception for cache-related errors.
 */
class CacheException extends HubException
{
    public static function writeError(string $path, ?\Throwable $previous = null): self
    {
        return new self("Failed to write to cache at: {$path}", 0, $previous);
    }

    public static function readError(string $path, ?\Throwable $previous = null): self
    {
        return new self("Failed to read from cache at: {$path}", 0, $previous);
    }

    public static function directoryNotWritable(string $path): self
    {
        return new self("Cache directory is not writable: {$path}");
    }

    public static function corruptedEntry(string $path): self
    {
        return new self("Corrupted cache entry at: {$path}");
    }
}
