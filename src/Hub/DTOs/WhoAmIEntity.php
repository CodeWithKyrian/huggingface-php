<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\WhoAmIType;

abstract readonly class WhoAmIEntity extends WhoAmIBase
{
    public function __construct(
        string $id,
        WhoAmIType $type,
        string $name,
        public string $fullname,
        public ?string $email,
        public bool $canPay,
        public string $avatarUrl,
        public ?int $periodEnd,
    ) {
        parent::__construct($id, $type, $name);
    }
}
