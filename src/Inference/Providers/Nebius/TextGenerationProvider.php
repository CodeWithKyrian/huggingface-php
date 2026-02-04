<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Nebius;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseTextGenerationProvider;

/**
 * Nebius AI text generation provider.
 *
 * @see https://docs.nebius.ai/studio/inference/models/completions/
 */
class TextGenerationProvider extends BaseTextGenerationProvider
{
    private const BASE_URL = 'https://api.studio.nebius.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Nebius, self::BASE_URL);
    }
}
