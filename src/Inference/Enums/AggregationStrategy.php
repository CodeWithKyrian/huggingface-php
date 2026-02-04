<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Enums;

/**
 * Strategy used to fuse tokens based on model predictions.
 */
enum AggregationStrategy: string
{
    /** Do not aggregate tokens */
    case None = 'none';

    /** Group consecutive tokens with the same label in a single entity */
    case Simple = 'simple';

    /** Similar to "simple", also preserves word integrity (use the label predicted for the first token) */
    case First = 'first';

    /** Similar to "simple", also preserves word integrity (uses the label with the highest score, averaged) */
    case Average = 'average';

    /** Similar to "simple", also preserves word integrity (uses the label with the highest score) */
    case Max = 'max';
}
