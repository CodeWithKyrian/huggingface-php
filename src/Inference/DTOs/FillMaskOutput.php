<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * A single fill mask prediction.
 */
final readonly class FillMaskOutput
{
    /**
     * @param float  $score    Confidence score
     * @param int    $token    Token ID
     * @param string $tokenStr Token string
     * @param string $sequence Full sequence with token filled in
     */
    public function __construct(
        public float $score,
        public int $token,
        public string $tokenStr,
        public string $sequence,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            score: (float) ($data['score'] ?? 0.0),
            token: (int) ($data['token'] ?? 0),
            tokenStr: $data['token_str'] ?? '',
            sequence: $data['sequence'] ?? '',
        );
    }
}
