<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * A chunk from a streaming chat completion response.
 */
final readonly class ChatCompletionStreamChunk
{
    /**
     * @param string                       $id      Unique chunk identifier
     * @param string                       $model   Model that generated the chunk
     * @param int                          $created Unix timestamp of creation
     * @param array<int, ChatStreamChoice> $choices Delta updates
     */
    public function __construct(
        public string $id,
        public string $model,
        public int $created,
        public array $choices,
    ) {}

    /**
     * Create from API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            model: $data['model'] ?? '',
            created: $data['created'] ?? time(),
            choices: array_map(
                static fn (array $c) => ChatStreamChoice::fromArray($c),
                $data['choices'] ?? []
            ),
        );
    }

    /**
     * Get the delta content from the first choice.
     */
    public function content(): string
    {
        return $this->choices[0]->delta->content ?? '';
    }

    /**
     * Get the finish reason (if this is the final chunk).
     */
    public function finishReason(): ?string
    {
        return $this->choices[0]->finishReason ?? null;
    }

    /**
     * Check if this is the final chunk.
     */
    public function isFinished(): bool
    {
        return null !== $this->finishReason();
    }
}
