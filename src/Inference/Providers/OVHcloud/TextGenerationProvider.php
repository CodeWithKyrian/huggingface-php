<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\OVHcloud;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseTextGenerationProvider;

/**
 * OVHcloud text generation provider.
 *
 * @see https://www.ovh.com/en/ai/
 */
class TextGenerationProvider extends BaseTextGenerationProvider
{
    private const BASE_URL = 'https://oai.endpoints.kepler.ai.cloud.ovh.net';

    public function __construct()
    {
        parent::__construct(InferenceProvider::OVHcloud, self::BASE_URL);
    }
}
