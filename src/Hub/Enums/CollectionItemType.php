<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

/**
 * Types of items that can be added to a collection.
 */
enum CollectionItemType: string
{
    case Model = 'model';
    case Dataset = 'dataset';
    case Space = 'space';
    case Paper = 'paper';
    case Collection = 'collection';
}
