<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\WhoAmIType;

final readonly class WhoAmIApp extends WhoAmIBase
{
    /**
     * @param null|array{entities: string[], role: string} $scope
     */
    public function __construct(
        string $id,
        string $name,
        public ?array $scope,
    ) {
        parent::__construct(
            id: $id,
            type: WhoAmIType::App,
            name: $name,
        );
    }

    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            scope: $data['scope'] ?? null,
        );
    }
}
