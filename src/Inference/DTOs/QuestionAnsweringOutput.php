<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Output from a question answering request.
 */
final readonly class QuestionAnsweringOutput
{
    /**
     * @param string $answer The extracted answer
     * @param float  $score  Confidence score
     * @param int    $start  Start position in context
     * @param int    $end    End position in context
     */
    public function __construct(
        public string $answer,
        public float $score,
        public int $start,
        public int $end,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            answer: $data['answer'] ?? '',
            score: (float) ($data['score'] ?? 0.0),
            start: (int) ($data['start'] ?? 0),
            end: (int) ($data['end'] ?? 0),
        );
    }
}
