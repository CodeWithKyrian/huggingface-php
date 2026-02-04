<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Sentence similarity specific helper for HF Inference.
 */
class SentenceSimilarityProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response) || !$this->isAllNumeric($response)) {
            throw new OutputValidationException(
                'Expected array of numbers from sentence similarity API',
                $response
            );
        }

        return $response;
    }

    /**
     * @param array<mixed> $arr
     */
    private function isAllNumeric(array $arr): bool
    {
        foreach ($arr as $item) {
            if (!is_numeric($item)) {
                return false;
            }
        }

        return true;
    }
}
