<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Http;

/**
 * Parses Server-Sent Events (SSE) from a stream of raw chunks.
 *
 * Use this for SSE endpoints like chat completion streaming.
 */
final class EventStreamResponse
{
    /**
     * @param \Generator<string> $chunks Raw data chunks from CurlConnector::stream()
     */
    public function __construct(
        private readonly \Generator $chunks,
    ) {}

    /**
     * Iterate over SSE events parsed from the stream.
     *
     * @return \Generator<array{event?: string, data: string, id?: string}>
     */
    public function events(): \Generator
    {
        $buffer = '';
        $event = [];

        foreach ($this->chunks as $chunk) {
            $buffer .= $chunk;

            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newlinePos);
                $buffer = substr($buffer, $newlinePos + 1);
                $line = rtrim($line, "\r");

                if ('' === $line) {
                    if (!empty($event)) {
                        yield $event;
                        $event = [];
                    }

                    continue;
                }

                if (str_starts_with($line, 'data: ')) {
                    $data = substr($line, 6);
                    if (isset($event['data'])) {
                        $event['data'] .= "\n".$data;
                    } else {
                        $event['data'] = $data;
                    }
                } elseif (str_starts_with($line, 'event: ')) {
                    $event['event'] = substr($line, 7);
                } elseif (str_starts_with($line, 'id: ')) {
                    $event['id'] = substr($line, 4);
                } elseif (str_starts_with($line, ':')) {
                    continue;
                }
            }
        }

        if (!empty($event)) {
            yield $event;
        }
    }
}
