<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

enum AuthorType: string
{
    case User = 'user';
    case Org = 'org';
}
