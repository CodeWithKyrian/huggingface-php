<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

/**
 * Represents a commit in a Hugging Face repository.
 */
final readonly class RepoCommit extends Resource
{
    /**
     * @param string                                            $id        The commit OID (SHA hash)
     * @param string                                            $title     The commit title (first line of message)
     * @param string                                            $message   The full commit message
     * @param array<array{username: string, avatarUrl: string}> $authors   List of commit authors
     * @param \DateTimeImmutable                                $createdAt When the commit was created
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $message,
        public readonly array $authors,
        public readonly \DateTimeImmutable $createdAt,
    ) {}

    /**
     * Get the short commit ID (first 7 characters).
     */
    public function shortId(): string
    {
        return substr($this->id, 0, 7);
    }

    /**
     * Get the primary author's username.
     */
    public function authorName(): ?string
    {
        return $this->authors[0]['username'] ?? null;
    }

    /**
     * Get the primary author's avatar URL.
     */
    public function authorAvatar(): ?string
    {
        return $this->authors[0]['avatarUrl'] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $authors = array_map(
            static fn (array $author) => [
                'username' => $author['user'] ?? $author['username'] ?? '',
                'avatarUrl' => $author['avatar'] ?? $author['avatarUrl'] ?? '',
            ],
            $data['authors'] ?? []
        );

        $createdAt = self::parseDateTime($data['date'] ?? $data['createdAt'] ?? '');

        return new self(
            id: $data['id'] ?? $data['oid'] ?? '',
            title: $data['title'] ?? '',
            message: $data['message'] ?? '',
            authors: $authors,
            createdAt: $createdAt,
        );
    }
}
