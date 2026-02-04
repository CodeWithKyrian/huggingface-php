<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Together;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseConversationalProvider;

/**
 * Together AI chat completion provider.
 *
 * @see https://docs.together.ai/reference/chat-completions
 */
class ChatProvider extends BaseConversationalProvider
{
    private const BASE_URL = 'https://api.together.xyz';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Together, self::BASE_URL);
    }
}
