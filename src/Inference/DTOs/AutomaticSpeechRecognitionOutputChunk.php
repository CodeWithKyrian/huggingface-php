<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * A chunk of text from speech recognition with timestamps.
 */
class AutomaticSpeechRecognitionOutputChunk
{
    /**
     * @param string                    $text      The transcribed text chunk
     * @param array{0: float, 1: float} $timestamp Start and end timestamps
     */
    public function __construct(
        public string $text,
        public array $timestamp,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'] ?? '',
            timestamp: $data['timestamp'] ?? [0.0, 0.0],
        );
    }
}
