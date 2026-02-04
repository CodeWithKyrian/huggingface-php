<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * A choice delta in a streaming response.
 */
final readonly class ChatStreamChoice
{
    public function __construct(
        public int $index,
        public ChatDelta $delta,
        public ?string $finishReason = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            index: $data['index'] ?? 0,
            delta: ChatDelta::fromArray($data['delta'] ?? []),
            finishReason: $data['finish_reason'] ?? null,
        );
    }
}
