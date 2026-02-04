<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Delta content in a streaming response.
 *
 * For reasoning models, the delta may include reasoning_content
 * showing incremental chain-of-thought reasoning.
 */
final readonly class ChatDelta
{
    public function __construct(
        public ?string $role = null,
        public ?string $content = null,
        public ?string $reasoningContent = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            role: $data['role'] ?? null,
            content: $data['content'] ?? null,
            reasoningContent: $data['reasoning_content'] ?? null,
        );
    }
}
