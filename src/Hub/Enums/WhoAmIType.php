<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

enum WhoAmIType: string
{
    case User = 'user';
    case Org = 'org';
    case App = 'app';
}
