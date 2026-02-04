<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Sambanova;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseFeatureExtractionProvider;

/**
 * Sambanova embeddings provider.
 *
 * @see https://docs.sambanova.ai/
 */
class FeatureExtractionProvider extends BaseFeatureExtractionProvider
{
    private const BASE_URL = 'https://api.sambanova.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Sambanova, self::BASE_URL);
    }
}
