<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Fireworks;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseConversationalProvider;

/**
 * Fireworks AI chat completion provider.
 *
 * @see https://docs.fireworks.ai/api-reference/post-chatcompletions
 */
class ChatProvider extends BaseConversationalProvider
{
    private const BASE_URL = 'https://api.fireworks.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::FireworksAi, self::BASE_URL);
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return 'inference/v1/chat/completions';
    }
}
