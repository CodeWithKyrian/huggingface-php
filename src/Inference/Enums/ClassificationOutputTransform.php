<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Enums;

enum ClassificationOutputTransform: string
{
    case Sigmoid = 'sigmoid';
    case Softmax = 'softmax';
    case None = 'none';
}
