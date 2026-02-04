<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Cohere;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseConversationalProvider;

/**
 * Cohere chat completion provider.
 *
 * @see https://docs.cohere.com/reference/chat
 */
class ChatProvider extends BaseConversationalProvider
{
    private const BASE_URL = 'https://api.cohere.com';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Cohere, self::BASE_URL);
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return 'compatibility/v1/chat/completions';
    }
}
