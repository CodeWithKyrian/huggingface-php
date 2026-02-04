<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\CurlConnector;
use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\ChatCompletionOutput;
use Codewithkyrian\HuggingFace\Inference\DTOs\ChatCompletionStreamChunk;
use Codewithkyrian\HuggingFace\Inference\DTOs\ChatCompletionTool;
use Codewithkyrian\HuggingFace\Inference\DTOs\ChatMessage;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for chat completion requests.
 *
 * Provides a conversational way to build chat requests with proper typing.
 *
 *  Basic usage
 * ```php
 * $response = $hf->inference()
 *     ->model('meta-llama/Llama-3.1-8B-Instruct')
 *     ->chatCompletion()
 *     ->system('You are a helpful assistant.')
 *     ->user('What is PHP?')
 *     ->maxTokens(100)
 *     ->generate();
 *
 * echo $response->content();
 * ```
 *
 *  Streaming
 * ```php
 * foreach ($hf->inference()
 *     ->model('meta-llama/Llama-3.1-8B-Instruct')
 *     ->chatCompletion()
 *     ->user('Tell me a story')
 *     ->stream() as $chunk) {
 *     echo $chunk->content();
 * }
 * ```
 */
final class ChatCompletionBuilder
{
    /** @var array<ChatMessage> */
    private array $messages = [];

    private ?int $maxTokens = null;
    private ?float $temperature = null;
    private ?float $topP = null;

    /** @var null|array<string>|string */
    private array|string|null $stop = null;
    private ?int $seed = null;

    private ?float $frequencyPenalty = null;
    private ?float $presencePenalty = null;
    private ?bool $logprobs = null;
    private ?int $topLogprobs = null;

    /** @var null|array<string, mixed> */
    private ?array $responseFormat = null;

    /** @var ChatCompletionTool[] */
    private array $tools = [];

    /** @var null|array<string, mixed>|string */
    private array|string|null $toolChoice = null;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly HttpConnector $http,
        private readonly CurlConnector $curl,
        private readonly ProviderHelperInterface $helper,
        private readonly string $model,
        private readonly string $url,
        private readonly array $headers,
    ) {}

    /**
     * Add a system message.
     *
     * System messages set the behavior and context for the assistant.
     */
    public function system(string $content): self
    {
        $this->messages[] = ChatMessage::system($content);

        return $this;
    }

    /**
     * Add a user message.
     */
    public function user(string $content): self
    {
        $this->messages[] = ChatMessage::user($content);

        return $this;
    }

    /**
     * Add an assistant message.
     *
     * Used for providing examples or context from previous responses.
     */
    public function assistant(string $content): self
    {
        $this->messages[] = ChatMessage::assistant($content);

        return $this;
    }

    /**
     * Add a message with a specific role.
     */
    public function message(string $role, string $content): self
    {
        $this->messages[] = new ChatMessage($role, $content);

        return $this;
    }

    /**
     * Add multiple messages at once.
     *
     * @param array<array{role: string, content: string}|ChatMessage> $messages
     */
    public function messages(array $messages): self
    {
        foreach ($messages as $message) {
            if ($message instanceof ChatMessage) {
                $this->messages[] = $message;
            } else {
                $this->messages[] = new ChatMessage($message['role'], $message['content']);
            }
        }

        return $this;
    }

    /**
     * Set the maximum number of tokens to generate.
     */
    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    /**
     * Set the sampling temperature (0.0 - 2.0).
     *
     * Higher values make output more random, lower values more focused.
     */
    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Set nucleus sampling parameter (0.0 - 1.0).
     *
     * Only sample from tokens whose cumulative probability exceeds this value.
     */
    public function topP(float $topP): self
    {
        $this->topP = $topP;

        return $this;
    }

    /**
     * Set stop sequence(s).
     *
     * Generation stops when any of these sequences is encountered.
     *
     * @param array<string>|string $stop
     */
    public function stop(array|string $stop): self
    {
        $this->stop = $stop;

        return $this;
    }

    /**
     * Set random seed for reproducibility.
     */
    public function seed(int $seed): self
    {
        $this->seed = $seed;

        return $this;
    }

    /**
     * Number between -2.0 and 2.0. Positive values penalize new tokens based on their
     * existing frequency in the text so far.
     */
    public function frequencyPenalty(float $frequencyPenalty): self
    {
        $this->frequencyPenalty = $frequencyPenalty;

        return $this;
    }

    /**
     * Number between -2.0 and 2.0. Positive values penalize new tokens based on whether
     * they appear in the text so far.
     */
    public function presencePenalty(float $presencePenalty): self
    {
        $this->presencePenalty = $presencePenalty;

        return $this;
    }

    /**
     * Whether to return log probabilities of the output tokens or not.
     *
     * @param bool     $logprobs    Whether to return logprobs
     * @param null|int $topLogprobs Number of most likely tokens to return at each position (0-20)
     */
    public function logprobs(bool $logprobs = true, ?int $topLogprobs = null): self
    {
        $this->logprobs = $logprobs;
        $this->topLogprobs = $topLogprobs;

        return $this;
    }

    /**
     * Set the format of the response.
     *
     * @param array<string, mixed> $responseFormat The format, e.g. ['type' => 'json_object']
     */
    public function responseFormat(array $responseFormat): self
    {
        $this->responseFormat = $responseFormat;

        return $this;
    }

    /**
     * Add a single tool.
     *
     * @param ChatCompletionTool $tool The tool definition
     */
    public function tool(ChatCompletionTool $tool): self
    {
        $this->tools[] = $tool;

        return $this;
    }

    /**
     * Add multiple tools.
     *
     * @param array<ChatCompletionTool> $tools List of tool definitions
     */
    public function tools(array $tools): self
    {
        foreach ($tools as $tool) {
            $this->tools[] = $tool;
        }

        return $this;
    }

    /**
     * Controls which (if any) tool is called by the model.
     *
     * @param array<string, mixed>|string $toolChoice "auto", "none", "required", or a specific tool definition
     */
    public function toolChoice(array|string $toolChoice): self
    {
        $this->toolChoice = $toolChoice;

        return $this;
    }

    /**
     * Execute the request and return the response.
     *
     * @throws ProviderApiException On API errors
     */
    public function generate(): ChatCompletionOutput
    {
        $payload = $this->buildPayload(stream: false);
        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        $data = $this->helper->getResponse($response->json());

        return ChatCompletionOutput::fromArray($data);
    }

    /**
     * Execute the request and stream the response.
     *
     * @return \Generator<ChatCompletionStreamChunk>
     */
    public function stream(): \Generator
    {
        $payload = $this->buildPayload(stream: true);
        $body = $this->helper->preparePayload($payload, $this->model);

        $streamResponse = $this->curl->streamEvents($this->url, $body, $this->headers);

        foreach ($streamResponse->events() as $event) {
            $data = $event['data'] ?? null;

            if (null === $data || '[DONE]' === $data) {
                continue;
            }

            $decoded = json_decode($data, true);
            if (null !== $decoded) {
                yield ChatCompletionStreamChunk::fromArray($decoded);
            }
        }
    }

    /**
     * Build the request payload.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(bool $stream): array
    {
        $payload = [
            'messages' => array_map(static fn (ChatMessage $m) => $m->toArray(), $this->messages),
        ];

        if ($stream) {
            $payload['stream'] = true;
        }

        if (null !== $this->maxTokens) {
            $payload['max_tokens'] = $this->maxTokens;
        }

        if (null !== $this->temperature) {
            $payload['temperature'] = $this->temperature;
        }

        if (null !== $this->topP) {
            $payload['top_p'] = $this->topP;
        }

        if (null !== $this->stop) {
            $payload['stop'] = $this->stop;
        }

        if (null !== $this->seed) {
            $payload['seed'] = $this->seed;
        }

        if (null !== $this->frequencyPenalty) {
            $payload['frequency_penalty'] = $this->frequencyPenalty;
        }

        if (null !== $this->presencePenalty) {
            $payload['presence_penalty'] = $this->presencePenalty;
        }

        if (null !== $this->logprobs) {
            $payload['logprobs'] = $this->logprobs;
            if (null !== $this->topLogprobs) {
                $payload['top_logprobs'] = $this->topLogprobs;
            }
        }

        if (null !== $this->responseFormat) {
            $payload['response_format'] = $this->responseFormat;
        }

        if (!empty($this->tools)) {
            $payload['tools'] = array_map(static fn (ChatCompletionTool $t) => $t->toArray(), $this->tools);
            if (null !== $this->toolChoice) {
                $payload['tool_choice'] = $this->toolChoice;
            }
        }

        return $payload;
    }
}
