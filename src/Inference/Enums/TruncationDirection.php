<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Enums;

enum TruncationDirection: string
{
    case Left = 'left';
    case Right = 'right';
}
