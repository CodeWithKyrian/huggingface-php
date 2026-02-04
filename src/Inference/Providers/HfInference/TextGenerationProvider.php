<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Text generation specific helper for HF Inference.
 */
class TextGenerationProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        // Handle array response
        if (\is_array($response)) {
            $items = $response;

            // Check if first element has generated_text
            if (isset($items[0]['generated_text'])) {
                return $items[0];
            }

            // If it's a single item with generated_text
            if (isset($response['generated_text'])) {
                return $response;
            }
        }

        throw new OutputValidationException(
            'Expected response with "generated_text" from text generation API',
            $response
        );
    }
}
