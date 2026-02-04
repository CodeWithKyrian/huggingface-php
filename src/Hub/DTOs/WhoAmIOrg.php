<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\WhoAmIType;

final readonly class WhoAmIOrg extends WhoAmIEntity
{
    public function __construct(
        string $id,
        string $name,
        string $fullname,
        ?string $email,
        bool $canPay,
        string $avatarUrl,
        ?int $periodEnd,
    ) {
        parent::__construct(
            id: $id,
            type: WhoAmIType::Org,
            name: $name,
            fullname: $fullname,
            email: $email,
            canPay: $canPay,
            avatarUrl: $avatarUrl,
            periodEnd: $periodEnd,
        );
    }

    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            fullname: $data['fullname'],
            email: $data['email'] ?? null,
            canPay: $data['canPay'] ?? false,
            avatarUrl: $data['avatarUrl'],
            periodEnd: $data['periodEnd'] ?? null,
        );
    }
}
