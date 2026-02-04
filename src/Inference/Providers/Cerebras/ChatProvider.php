<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Cerebras;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseConversationalProvider;

/**
 * Cerebras chat completion provider.
 *
 * @see https://inference-docs.cerebras.ai/api-reference/chat-completions
 */
class ChatProvider extends BaseConversationalProvider
{
    private const BASE_URL = 'https://api.cerebras.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Cerebras, self::BASE_URL);
    }
}
