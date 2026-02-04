<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Output from a chat completion request.
 */
final readonly class ChatCompletionOutput
{
    /**
     * @param string                 $id      Unique response identifier
     * @param string                 $model   Model that generated the response
     * @param int                    $created Unix timestamp of creation
     * @param array<int, ChatChoice> $choices Generated completions
     * @param null|Usage             $usage   Token usage statistics
     */
    public function __construct(
        public string $id,
        public string $model,
        public int $created,
        public array $choices,
        public ?Usage $usage = null,
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
                static fn (array $c) => ChatChoice::fromArray($c),
                $data['choices'] ?? []
            ),
            usage: isset($data['usage']) ? Usage::fromArray($data['usage']) : null,
        );
    }

    /**
     * Get the first choice's message content.
     *
     * Convenience method for the most common use case.
     */
    public function content(): string
    {
        return $this->choices[0]->message->content ?? '';
    }

    /**
     * Get the first choice's finish reason.
     */
    public function finishReason(): ?string
    {
        return $this->choices[0]->finishReason ?? null;
    }
}
