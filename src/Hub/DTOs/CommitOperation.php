<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents a single operation in a commit.
 */
interface CommitOperation
{
    /**
     * Get the path of the file involved in the operation.
     */
    public function getPath(): string;

    /**
     * Get the operation type (addOrUpdate, delete, etc.).
     */
    public function getOperationType(): string;

    /**
     * Convert operation to NDJSON line.
     *
     * @param array<string, string> $lfsFiles
     */
    public function toNdJson(array $lfsFiles): string;
}
