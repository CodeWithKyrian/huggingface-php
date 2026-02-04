<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

final class CommitOpDelete implements CommitOperation
{
    public function __construct(
        private readonly string $path
    ) {}

    public function getPath(): string
    {
        return $this->path;
    }

    public function getOperationType(): string
    {
        return 'delete';
    }

    /**
     * @param array<string, string> $lfsFiles
     */
    public function toNdJson(array $lfsFiles): string
    {
        return json_encode([
            'key' => 'deletedFile',
            'value' => ['path' => $this->path],
        ]);
    }
}
