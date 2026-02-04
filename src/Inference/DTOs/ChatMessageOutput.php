<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * A message in a chat completion response.
 *
 * For reasoning models (like GLM-4, o1, etc.), the response may include
 * reasoning_content which shows the model's chain-of-thought before the answer.
 */
final readonly class ChatMessageOutput
{
    public function __construct(
        public string $role,
        public string $content,
        public ?string $reasoningContent = null,
    ) {}

    /**
     * Check if this message contains reasoning content.
     */
    public function hasReasoning(): bool
    {
        return null !== $this->reasoningContent && '' !== $this->reasoningContent;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            role: $data['role'] ?? 'assistant',
            content: $data['content'] ?? '',
            reasoningContent: $data['reasoning_content'] ?? null,
        );
    }
}
