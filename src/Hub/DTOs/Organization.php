<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents a Hugging Face organization.
 */
final readonly class Organization extends Resource
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $fullname,
        public ?string $avatarUrl,
        public bool $isEnterprise,
        /** @var string[] */
        public array $roleInOrg,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            fullname: $data['fullname'] ?? null,
            avatarUrl: $data['avatarUrl'] ?? null,
            isEnterprise: self::parseBool($data['isEnterprise'] ?? false),
            roleInOrg: (array) ($data['roleInOrg'] ?? []),
        );
    }
}
