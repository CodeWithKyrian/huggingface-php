<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Together;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseTextToImageProvider;

/**
 * Together AI image generation provider.
 *
 * @see https://docs.together.ai/reference/images
 */
class TextToImageProvider extends BaseTextToImageProvider
{
    private const BASE_URL = 'https://api.together.xyz';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Together, self::BASE_URL);
    }
}
