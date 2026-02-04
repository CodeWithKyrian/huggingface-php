<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\OpenAI;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseConversationalProvider;

/**
 * OpenAI chat completion provider.
 *
 * Note: OpenAI requires client-side routing only (direct API access with their key).
 *
 * @see https://platform.openai.com/docs/api-reference/chat
 */
class ChatProvider extends BaseConversationalProvider
{
    private const BASE_URL = 'https://api.openai.com';

    public function __construct()
    {
        parent::__construct(
            InferenceProvider::OpenAI,
            self::BASE_URL,
            clientSideRoutingOnly: true
        );
    }
}
