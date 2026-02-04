<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * A single choice in a chat completion response.
 */
final readonly class ChatChoice
{
    public function __construct(
        public int $index,
        public ChatMessageOutput $message,
        public ?string $finishReason = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            index: $data['index'] ?? 0,
            message: ChatMessageOutput::fromArray($data['message'] ?? []),
            finishReason: $data['finish_reason'] ?? null,
        );
    }
}
