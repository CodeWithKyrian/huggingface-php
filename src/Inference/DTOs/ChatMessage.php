<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * A single message in a chat conversation.
 */
final readonly class ChatMessage
{
    /**
     * @param string $role    Message role: 'system', 'user', or 'assistant'
     * @param string $content Message content
     */
    public function __construct(
        public string $role,
        public string $content,
    ) {}

    /**
     * Create a system message.
     *
     * System messages set the behavior and context for the assistant.
     */
    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    /**
     * Create a user message.
     */
    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    /**
     * Create an assistant message.
     *
     * Used for providing examples or context from previous responses.
     */
    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }

    /**
     * Convert to array for API payload.
     *
     * @return array{role: string, content: string}
     */
    public function toArray(): array
    {
        return ['role' => $this->role, 'content' => $this->content];
    }
}
