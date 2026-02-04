<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Bounding box coordinates for object detection.
 */
class BoundingBox
{
    public function __construct(
        public int $xmin,
        public int $ymin,
        public int $xmax,
        public int $ymax,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            xmin: (int) ($data['xmin'] ?? 0),
            ymin: (int) ($data['ymin'] ?? 0),
            xmax: (int) ($data['xmax'] ?? 0),
            ymax: (int) ($data['ymax'] ?? 0),
        );
    }
}
