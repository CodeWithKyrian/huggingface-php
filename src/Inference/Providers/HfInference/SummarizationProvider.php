<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Summarization specific helper for HF Inference.
 */
class SummarizationProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        if (\is_array($response) && isset($response[0]['summary_text'])) {
            return $response[0];
        }

        if (\is_array($response) && isset($response['summary_text'])) {
            return $response;
        }

        throw new OutputValidationException(
            'Expected response with "summary_text" from summarization API',
            $response
        );
    }
}
