<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Translation specific helper for HF Inference.
 */
class TranslationProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        if (\is_array($response) && isset($response[0]['translation_text'])) {
            return 1 === \count($response) ? $response[0] : $response;
        }

        if (\is_array($response) && isset($response['translation_text'])) {
            return $response;
        }

        throw new OutputValidationException(
            'Expected response with "translation_text" from translation API',
            $response
        );
    }
}
