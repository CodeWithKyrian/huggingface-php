<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Scaleway;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseFeatureExtractionProvider;

/**
 * Scaleway feature extraction (embeddings) provider.
 *
 * @see https://www.scaleway.com/en/docs/ai/generative-apis/
 */
class FeatureExtractionProvider extends BaseFeatureExtractionProvider
{
    private const BASE_URL = 'https://api.scaleway.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Scaleway, self::BASE_URL);
    }
}
