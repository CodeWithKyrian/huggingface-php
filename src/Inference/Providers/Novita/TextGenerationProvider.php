<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Novita;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseTextGenerationProvider;

/**
 * Novita AI text generation provider.
 *
 * Uses the v3 OpenAI-compatible API endpoint.
 *
 * @see https://novita.ai/docs/llm/api
 */
class TextGenerationProvider extends BaseTextGenerationProvider
{
    private const BASE_URL = 'https://api.novita.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Novita, self::BASE_URL);
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return 'v3/openai/chat/completions';
    }
}
