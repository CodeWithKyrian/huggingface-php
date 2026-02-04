<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Output for object detection tasks.
 */
class ObjectDetectionOutput
{
    public function __construct(
        public string $label,
        public float $score,
        public BoundingBox $box,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'] ?? '',
            score: (float) ($data['score'] ?? 0),
            box: BoundingBox::fromArray($data['box'] ?? []),
        );
    }
}
