<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Output from a summarization request.
 */
final readonly class SummarizationOutput
{
    /**
     * @param string $summaryText The generated summary
     */
    public function __construct(
        public string $summaryText,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            summaryText: $data['summary_text'] ?? '',
        );
    }
}
