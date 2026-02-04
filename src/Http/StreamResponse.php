<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Http;

/**
 * Wraps a raw streaming response from cURL.
 *
 * Provides access to raw data chunks as they arrive from the server.
 */
final class StreamResponse
{
    /**
     * @param \Generator<string> $chunks Raw data chunks
     */
    public function __construct(
        private readonly \Generator $chunks,
    ) {}

    /**
     * Iterate over raw chunks as they arrive.
     *
     * @return \Generator<string>
     */
    public function chunks(): \Generator
    {
        foreach ($this->chunks as $chunk) {
            yield $chunk;
        }
    }
}
