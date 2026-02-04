<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Output from a translation request.
 */
final readonly class TranslationOutput
{
    /**
     * @param string $translationText The translated text
     */
    public function __construct(
        public string $translationText,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            translationText: $data['translation_text'] ?? '',
        );
    }
}
