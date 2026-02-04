<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Output for token classification tasks.
 */
class TokenClassificationOutput
{
    public function __construct(
        public string $entityGroup,
        public float $score,
        public string $word,
        public int $start,
        public int $end,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entityGroup: $data['entity_group'] ?? $data['label'] ?? '',
            score: (float) ($data['score'] ?? 0),
            word: $data['word'] ?? '',
            start: (int) ($data['start'] ?? 0),
            end: (int) ($data['end'] ?? 0),
        );
    }
}
