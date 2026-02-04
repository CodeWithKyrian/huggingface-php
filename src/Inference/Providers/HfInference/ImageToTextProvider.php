<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Image to text specific helper for HF Inference.
 */
class ImageToTextProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        if (\is_array($response) && isset($response[0]['generated_text'])) {
            return $response[0];
        }

        if (\is_array($response) && isset($response['generated_text'])) {
            return $response;
        }

        throw new OutputValidationException(
            'Expected response with "generated_text" from image-to-text API',
            $response
        );
    }
}
