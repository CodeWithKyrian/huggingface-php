<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\AuthorType;

/**
 * Represents an author (User or Organization) in the API.
 */
final readonly class Author extends Resource
{
    /**
     * @param string      $name            the username or organization name
     * @param null|string $fullname        the full name of the user or organization
     * @param null|string $avatarUrl       the URL of the avatar
     * @param bool        $isHf            whether the author is a Hugging Face user/org
     * @param bool        $isHfAdmin       whether the author is a Hugging Face admin
     * @param bool        $isMod           whether the author is a moderator
     * @param AuthorType  $type            the type of author (user or org)
     * @param null|int    $followerCount   the number of followers
     * @param bool        $isEnterprise    whether the organization is an enterprise user (Org only)
     * @param bool        $isPro           whether the user is a pro user (User only)
     * @param null|string $id              the user ID (User only)
     * @param null|bool   $isUserFollowing whether the authenticated user is following this author
     */
    public function __construct(
        public string $name,
        public ?string $fullname,
        public ?string $avatarUrl,
        public bool $isHf,
        public bool $isHfAdmin,
        public bool $isMod,
        public AuthorType $type,
        public ?int $followerCount = null,
        public bool $isEnterprise = false,
        public bool $isPro = false,
        public ?string $id = null,
        public ?bool $isUserFollowing = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            name: $data['name'],
            fullname: $data['fullname'] ?? null,
            avatarUrl: $data['avatarUrl'] ?? null,
            isHf: $data['isHf'] ?? false,
            isHfAdmin: $data['isHfAdmin'] ?? false,
            isMod: $data['isMod'] ?? false,
            type: AuthorType::tryFrom($data['type'] ?? '') ?? AuthorType::User,
            followerCount: $data['followerCount'] ?? null,
            isEnterprise: $data['isEnterprise'] ?? false,
            isPro: $data['isPro'] ?? false,
            id: $data['_id'] ?? null,
            isUserFollowing: $data['isUserFollowing'] ?? null,
        );
    }
}
