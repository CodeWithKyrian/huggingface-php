<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Hyperbolic;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseTextGenerationProvider;

/**
 * Hyperbolic text generation provider.
 *
 * @see https://docs.hyperbolic.xyz/docs
 */
class TextGenerationProvider extends BaseTextGenerationProvider
{
    private const BASE_URL = 'https://api.hyperbolic.xyz';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Hyperbolic, self::BASE_URL);
    }
}
