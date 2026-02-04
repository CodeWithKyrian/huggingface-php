<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\AutoRouter;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;

/**
 * Chat completion specific helper for the auto-router.
 *
 * Uses the /v1/chat/completions endpoint for OpenAI-compatible chat.
 */
class ChatProvider extends Provider
{
    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return 'v1/chat/completions';
    }
}
