<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\WhoAmIType;

abstract readonly class WhoAmIBase extends Resource
{
    public function __construct(
        public string $id,
        public WhoAmIType $type,
        public string $name,
    ) {}
}
