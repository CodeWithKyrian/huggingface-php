<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

/**
 * Sorting options for collection search results.
 */
enum CollectionSortField: string
{
    case LastModified = 'lastModified';
    case Trending = 'trending';
    case Upvotes = 'upvotes';
}
