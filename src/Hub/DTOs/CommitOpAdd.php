<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

final class CommitOpAdd implements CommitOperation
{
    /**
     * @param string $path    the path in the repository
     * @param mixed  $content the content to upload
     */
    public function __construct(
        private readonly string $path,
        private mixed $content
    ) {}

    public function getPath(): string
    {
        return $this->path;
    }

    public function getOperationType(): string
    {
        return 'addOrUpdate';
    }

    /**
     * Get the content of the file.
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    public function getSize(): int
    {
        if (\is_resource($this->content)) {
            $stats = fstat($this->content);

            return $stats['size'];
        }

        return \strlen($this->content);
    }

    /**
     * @param array<string, string> $lfsFiles
     */
    public function toNdJson(array $lfsFiles): string
    {
        if (isset($lfsFiles[$this->path])) {
            // LFS Pointer
            return json_encode([
                'key' => 'lfsFile',
                'value' => [
                    'path' => $this->path,
                    'algo' => 'sha256',
                    'size' => $this->getSize(),
                    'oid' => $lfsFiles[$this->path],
                ],
            ]);
        }

        // Regular File (Base64)
        $content = $this->content;
        $contentString = \is_resource($content) ? stream_get_contents($content) : $content;

        // Reset stream if needed
        if (\is_resource($content)) {
            rewind($content);
        }

        return json_encode([
            'key' => 'file',
            'value' => [
                'path' => $this->path,
                'content' => base64_encode($contentString),
                'encoding' => 'base64',
            ],
        ]);
    }
}
