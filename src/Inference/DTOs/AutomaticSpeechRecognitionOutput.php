<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\DTOs;

/**
 * Output from an automatic speech recognition request.
 */
class AutomaticSpeechRecognitionOutput
{
    /**
     * @param string                                            $text   The transcribed text
     * @param null|array<AutomaticSpeechRecognitionOutputChunk> $chunks Audio chunks if timestamps enabled
     */
    public function __construct(
        public string $text,
        public ?array $chunks = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $chunks = null;
        if (isset($data['chunks']) && \is_array($data['chunks'])) {
            $chunks = array_map(
                static fn (array $item) => AutomaticSpeechRecognitionOutputChunk::fromArray($item),
                $data['chunks']
            );
        }

        return new self(
            text: $data['text'] ?? '',
            chunks: $chunks,
        );
    }
}
