<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Image classification specific helper for HF Inference.
 */
class ImageClassificationProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response)) {
            throw new OutputValidationException(
                'Expected array response from image classification API',
                $response
            );
        }

        foreach ($response as $item) {
            if (!isset($item['label'], $item['score'])) {
                throw new OutputValidationException(
                    'Expected image classification item with label and score',
                    $response
                );
            }
        }

        return $response;
    }
}
