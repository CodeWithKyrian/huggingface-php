<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Text classification specific helper for HF Inference.
 */
class TextClassificationProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response)) {
            throw new OutputValidationException(
                'Expected array response from text classification API',
                $response
            );
        }

        // HF returns [[{label, score}, ...]] - we return the inner array
        $output = $response[0] ?? $response;

        if (!\is_array($output)) {
            throw new OutputValidationException(
                'Expected array of classification results',
                $response
            );
        }

        foreach ($output as $item) {
            if (!isset($item['label']) || !isset($item['score'])) {
                throw new OutputValidationException(
                    'Expected classification item with label and score',
                    $response
                );
            }
        }

        return $output;
    }
}
