<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Zero-shot classification specific helper for HF Inference.
 */
class ZeroShotClassificationProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        // Handle legacy format with separate labels/scores arrays
        if (
            \is_array($response)
            && isset($response['labels'], $response['scores'])
            && \is_array($response['labels'])
            && \is_array($response['scores'])
            && \count($response['labels']) === \count($response['scores'])
        ) {
            $result = [];
            foreach ($response['labels'] as $i => $label) {
                $result[] = [
                    'label' => $label,
                    'score' => $response['scores'][$i],
                ];
            }

            return $result;
        }

        // Handle new format with array of {label, score} objects
        if (\is_array($response)) {
            foreach ($response as $item) {
                if (!isset($item['label'], $item['score'])) {
                    throw new OutputValidationException(
                        'Expected zero-shot classification item with label and score',
                        $response
                    );
                }
            }

            return $response;
        }

        throw new OutputValidationException(
            'Expected array response from zero-shot classification API',
            $response
        );
    }
}
