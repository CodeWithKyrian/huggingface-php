<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Groq;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseConversationalProvider;

/**
 * Groq chat completion provider.
 *
 * @see https://console.groq.com/docs/api-reference#chat
 */
class ChatProvider extends BaseConversationalProvider
{
    private const BASE_URL = 'https://api.groq.com';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Groq, self::BASE_URL);
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return 'openai/v1/chat/completions';
    }
}
