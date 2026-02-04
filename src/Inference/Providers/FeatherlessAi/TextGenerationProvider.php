<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\FeatherlessAi;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseTextGenerationProvider;

/**
 * Featherless AI text generation provider.
 *
 * @see https://docs.featherless.ai/
 */
class TextGenerationProvider extends BaseTextGenerationProvider
{
    private const BASE_URL = 'https://api.featherless.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::FeatherlessAi, self::BASE_URL);
    }
}
