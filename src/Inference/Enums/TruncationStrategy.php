<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Enums;

enum TruncationStrategy: string
{
    case DoNotTruncate = 'do_not_truncate';
    case LongestFirst = 'longest_first';
    case OnlyFirst = 'only_first';
    case OnlySecond = 'only_second';
}
