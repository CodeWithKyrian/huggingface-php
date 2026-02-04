<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\HfInference;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Question answering specific helper for HF Inference.
 */
class QuestionAnsweringProvider extends Provider
{
    public function getResponse(mixed $response): mixed
    {
        // Can be a single object or array of objects
        $item = \is_array($response) && isset($response[0]) ? $response[0] : $response;

        if (
            !\is_array($item)
            || !isset($item['answer'], $item['score'], $item['start'], $item['end'])
        ) {
            throw new OutputValidationException(
                'Expected question answering response with answer, score, start, and end',
                $response
            );
        }

        return $item;
    }
}
