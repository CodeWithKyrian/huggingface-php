<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Fill mask specific helper for HF Inference.
 */
class FillMaskProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response)) {
            throw new OutputValidationException(
                'Expected array response from fill-mask API',
                $response
            );
        }

        foreach ($response as $item) {
            if (!isset($item['score'], $item['sequence'], $item['token'], $item['token_str'])) {
                throw new OutputValidationException(
                    'Expected fill-mask item with score, sequence, token, and token_str',
                    $response
                );
            }
        }

        return $response;
    }
}
