<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Output from a text generation request.
 */
final readonly class TextGenerationOutput
{
    /**
     * @param string $generatedText The generated text
     */
    public function __construct(
        public string $generatedText,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            generatedText: $data['generated_text'] ?? '',
        );
    }
}
