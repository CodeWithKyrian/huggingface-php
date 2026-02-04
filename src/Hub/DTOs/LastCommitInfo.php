<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents detailed commit information used in PathsInfo.
 */
final readonly class LastCommitInfo extends Resource
{
    public function __construct(
        public string $id,
        public string $title,
        public ?\DateTimeImmutable $date,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'] ?? '',
            title: $data['title'] ?? '',
            date: self::parseDateTime($data['date'] ?? null),
        );
    }
}
