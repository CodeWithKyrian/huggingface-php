<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

enum SpaceHardwareFlavor: string
{
    case CpuBasic = 'cpu-basic';
    case CpuUpgrade = 'cpu-upgrade';
    case CpuPerformance = 'cpu-performance';
    case CpuXl = 'cpu-xl';
    case Sprx8 = 'sprx8';
    case ZeroA10g = 'zero-a10g';
    case Inf2x6 = 'inf2x6';
    case T4Small = 't4-small';
    case T4Medium = 't4-medium';
    case L4x1 = 'l4x1';
    case L4x4 = 'l4x4';
    case L40sx1 = 'l40sx1';
    case L40sx4 = 'l40sx4';
    case L40sx8 = 'l40sx8';
    case A10gSmall = 'a10g-small';
    case A10gLarge = 'a10g-large';
    case A10gLargeX2 = 'a10g-largex2';
    case A10gLargeX4 = 'a10g-largex4';
    case A100Large = 'a100-large';
    case A100x4 = 'a100x4';
    case A100x8 = 'a100x8';
}
