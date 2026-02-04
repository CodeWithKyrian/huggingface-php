<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\License;

/**
 * Parsed dataset card data (YAML frontmatter).
 */
final readonly class DatasetCardData extends Resource
{
    /**
     * @param null|array<License>|License $license Single license, array of licenses, or null
     */
    public function __construct(
        public array|License|null $license,
        /** @var string[] */
        public array $tags,
        /** @var string[] */
        public array $languages,
        public ?string $task,
        /** @var string[] */
        public array $taskCategories,
        public ?int $size,
        /** @var array<string, mixed> */
        public array $extra,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $known = ['license', 'tags', 'languages', 'task', 'task_categories', 'size_categories'];

        $license = null;
        if (isset($data['license'])) {
            if (\is_array($data['license'])) {
                $license = array_filter(
                    array_map(
                        static fn ($l) => \is_string($l) ? License::tryFrom($l) : null,
                        $data['license']
                    )
                );
                $license = !empty($license) ? array_values($license) : null;
            } elseif (\is_string($data['license'])) {
                $license = License::tryFrom($data['license']);
            }
        }

        return new self(
            license: $license,
            tags: (array) ($data['tags'] ?? []),
            languages: (array) ($data['languages'] ?? $data['language'] ?? []),
            task: $data['task'] ?? null,
            taskCategories: (array) ($data['task_categories'] ?? []),
            size: isset($data['size_categories'][0])
            ? self::parseSizeCategory($data['size_categories'][0])
            : null,
            extra: array_diff_key($data, array_flip($known)),
        );
    }

    /**
     * Parse size category string to approximate row count.
     */
    private static function parseSizeCategory(string $category): ?int
    {
        // Format: "n<1K", "1K<n<10K", "10K<n<100K", etc.
        $map = [
            'n<1K' => 500,
            '1K<n<10K' => 5000,
            '10K<n<100K' => 50000,
            '100K<n<1M' => 500000,
            '1M<n<10M' => 5000000,
            '10M<n<100M' => 50000000,
            '100M<n<1B' => 500000000,
            'n>1B' => 1000000000,
        ];

        return $map[$category] ?? null;
    }
}
