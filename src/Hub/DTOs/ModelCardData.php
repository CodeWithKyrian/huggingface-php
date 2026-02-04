<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Parsed model card data (YAML frontmatter).
 */
final readonly class ModelCardData extends Resource
{
    public function __construct(
        public ?string $license,
        /** @var string[] */
        public array $tags,
        /** @var string[] */
        public array $datasets,
        /** @var string[] */
        public array $languages,
        public ?string $library,
        public ?string $pipelineTag,
        public ?string $baseModel,
        /** @var array<string, mixed> */
        public array $extra,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $known = ['license', 'tags', 'datasets', 'languages', 'library_name', 'pipeline_tag', 'base_model'];

        return new self(
            license: $data['license'] ?? null,
            tags: (array) ($data['tags'] ?? []),
            datasets: (array) ($data['datasets'] ?? []),
            languages: (array) ($data['languages'] ?? $data['language'] ?? []),
            library: $data['library_name'] ?? null,
            pipelineTag: $data['pipeline_tag'] ?? null,
            baseModel: $data['base_model'] ?? null,
            extra: array_diff_key($data, array_flip($known)),
        );
    }
}
