<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

/**
 * Types of repositories on the Hugging Face Hub.
 */
enum RepoType: string
{
    case Model = 'model';
    case Dataset = 'dataset';
    case Space = 'space';

    /**
     * Get the API path segment for this repo type.
     */
    public function apiPath(): string
    {
        return match ($this) {
            self::Model => 'models',
            self::Dataset => 'datasets',
            self::Space => 'spaces',
        };
    }
}
