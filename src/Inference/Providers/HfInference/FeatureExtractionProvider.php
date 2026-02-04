<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Feature extraction specific helper for HF Inference.
 */
class FeatureExtractionProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response)) {
            throw new OutputValidationException(
                'Expected array response from feature extraction API',
                $response
            );
        }

        // Validate it's a numeric array (embeddings)
        if (!$this->isNumericArray($response, 3)) {
            throw new OutputValidationException(
                'Expected numeric array from feature extraction API',
                $response
            );
        }

        return $response;
    }

    /**
     * @param array<mixed> $arr
     */
    private function isNumericArray(array $arr, int $maxDepth, int $curDepth = 0): bool
    {
        if ($curDepth > $maxDepth) {
            return false;
        }

        foreach ($arr as $item) {
            if (\is_array($item)) {
                if (!$this->isNumericArray($item, $maxDepth, $curDepth + 1)) {
                    return false;
                }
            } elseif (!is_numeric($item)) {
                return false;
            }
        }

        return true;
    }
}
