<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Token usage statistics.
 */
final readonly class Usage
{
    public function __construct(
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            promptTokens: $data['prompt_tokens'] ?? 0,
            completionTokens: $data['completion_tokens'] ?? 0,
            totalTokens: $data['total_tokens'] ?? 0,
        );
    }
}
