<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

/**
 * Sorting options for search results.
 */
enum SortField: string
{
    case Downloads = 'downloads';
    case Likes = 'likes';
    case LastModified = 'lastModified';
    case Trending = 'trendingScore';
    case Created = 'createdAt';
}
