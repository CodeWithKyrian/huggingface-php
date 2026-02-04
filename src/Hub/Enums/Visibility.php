<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

/**
 * Visibility options for repositories.
 */
enum Visibility: string
{
    case Public = 'public';
    case Private = 'private';
}
