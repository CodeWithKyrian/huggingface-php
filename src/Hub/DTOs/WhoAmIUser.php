<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\WhoAmIType;

final readonly class WhoAmIUser extends WhoAmIEntity
{
    /**
     * @param WhoAmIOrg[] $orgs
     */
    public function __construct(
        string $id,
        string $name,
        string $fullname,
        string $email,
        bool $canPay,
        string $avatarUrl,
        ?int $periodEnd,
        public bool $emailVerified,
        public bool $isPro,
        public array $orgs,
        public string $billingMode,
    ) {
        parent::__construct(
            id: $id,
            type: WhoAmIType::User,
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
            email: $data['email'],
            canPay: $data['canPay'] ?? false,
            avatarUrl: $data['avatarUrl'],
            periodEnd: $data['periodEnd'] ?? null,
            emailVerified: $data['emailVerified'] ?? false,
            isPro: $data['isPro'] ?? false,
            orgs: array_map(static fn ($org) => WhoAmIOrg::fromArray($org), $data['orgs'] ?? []),
            billingMode: $data['billingMode'] ?? 'postpaid',
        );
    }
}
