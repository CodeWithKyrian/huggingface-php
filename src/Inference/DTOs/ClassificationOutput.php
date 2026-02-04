<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * A single classification result.
 */
final readonly class ClassificationOutput
{
    /**
     * @param string $label Classification label
     * @param float  $score Confidence score
     */
    public function __construct(
        public string $label,
        public float $score,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'] ?? '',
            score: (float) ($data['score'] ?? 0.0),
        );
    }
}
