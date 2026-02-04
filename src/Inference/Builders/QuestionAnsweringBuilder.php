<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\DTOs\QuestionAnsweringOutput;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for question answering requests.
 */
final class QuestionAnsweringBuilder
{
    private ?bool $alignToWords = null;
    private ?int $docStride = null;
    private ?bool $handleImpossibleAnswer = null;
    private ?int $maxAnswerLen = null;
    private ?int $maxQuestionLen = null;
    private ?int $maxSeqLen = null;
    private ?int $topK = null;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly HttpConnector $http,
        private readonly ProviderHelperInterface $helper,
        private readonly string $model,
        private readonly string $url,
        private readonly array $headers,
    ) {}

    /**
     * Attempts to align the answer to real words. Improves quality on space separated languages.
     */
    public function alignToWords(bool $alignToWords): self
    {
        $this->alignToWords = $alignToWords;

        return $this;
    }

    /**
     * If the context is too long, it will be split in chunks. This controls the overlap size.
     */
    public function docStride(int $docStride): self
    {
        $this->docStride = $docStride;

        return $this;
    }

    /**
     * Whether to accept impossible as an answer.
     */
    public function handleImpossibleAnswer(bool $handleImpossibleAnswer): self
    {
        $this->handleImpossibleAnswer = $handleImpossibleAnswer;

        return $this;
    }

    /**
     * The maximum length of predicted answers.
     */
    public function maxAnswerLen(int $maxAnswerLen): self
    {
        $this->maxAnswerLen = $maxAnswerLen;

        return $this;
    }

    /**
     * The maximum length of the question after tokenization.
     */
    public function maxQuestionLen(int $maxQuestionLen): self
    {
        $this->maxQuestionLen = $maxQuestionLen;

        return $this;
    }

    /**
     * The maximum length of the total sentence (context + question) in tokens of each chunk.
     */
    public function maxSeqLen(int $maxSeqLen): self
    {
        $this->maxSeqLen = $maxSeqLen;

        return $this;
    }

    /**
     * The number of answers to return.
     */
    public function topK(int $topK): self
    {
        $this->topK = $topK;

        return $this;
    }

    /**
     * Execute the request and return the answer.
     *
     * @param string $question The question to answer
     * @param string $context  The context to extract the answer from
     */
    public function execute(string $question, string $context): QuestionAnsweringOutput
    {
        $payload = [
            'inputs' => [
                'question' => $question,
                'context' => $context,
            ],
        ];

        $parameters = array_filter([
            'align_to_words' => $this->alignToWords,
            'doc_stride' => $this->docStride,
            'handle_impossible_answer' => $this->handleImpossibleAnswer,
            'max_answer_len' => $this->maxAnswerLen,
            'max_question_len' => $this->maxQuestionLen,
            'max_seq_len' => $this->maxSeqLen,
            'top_k' => $this->topK,
        ], static fn ($v) => null !== $v);

        if (!empty($parameters)) {
            $payload['parameters'] = $parameters;
        }

        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        $data = $this->helper->getResponse($response->json());

        return QuestionAnsweringOutput::fromArray($data);
    }
}
