<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Zai;

use Codewithkyrian\HuggingFace\Inference\Enums\AuthMethod;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceTask;
use Codewithkyrian\HuggingFace\Inference\Providers\BaseConversationalProvider;

/**
 * ZAI (z.ai) chat completion provider.
 *
 * @see https://api.z.ai
 */
class ChatProvider extends BaseConversationalProvider
{
    private const BASE_URL = 'https://api.z.ai';

    public function __construct()
    {
        parent::__construct(InferenceProvider::Zai, self::BASE_URL);
    }

    public function prepareHeaders(
        AuthMethod $authMethod,
        ?string $accessToken = null,
        bool $isBinary = false,
        ?string $billTo = null,
    ): array {
        $headers = parent::prepareHeaders($authMethod, $accessToken, $isBinary, $billTo);

        $headers['x-source-channel'] = 'hugging_face';
        $headers['accept-language'] = 'en-US,en';

        return $headers;
    }

    public function makeRoute(string $model, ?InferenceTask $task = null): string
    {
        return 'api/paas/v4/chat/completions';
    }
}
