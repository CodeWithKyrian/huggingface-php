<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Http;

use Codewithkyrian\HuggingFace\Support\Utils;

/**
 * Manages partial download files for resumable downloads.
 *
 * Handles .part1, .part2, etc. files and merges them into the final file.
 */
final class DownloadPartHandler
{
    private string $basePath;

    /** @var string[] */
    private array $partFiles = [];

    public function __construct(string $targetPath)
    {
        $this->basePath = $targetPath;
        $this->discoverExistingParts();
    }

    /**
     * Get the total bytes already downloaded across all parts.
     */
    public function getTotalDownloadedBytes(): int
    {
        $total = 0;

        foreach ($this->partFiles as $file) {
            if (file_exists($file)) {
                $total += filesize($file) ?: 0;
            }
        }

        return $total;
    }

    /**
     * Get the next part index to use.
     */
    public function getNextPartIndex(): int
    {
        if (empty($this->partFiles)) {
            return 1;
        }

        return max(array_keys($this->partFiles)) + 1;
    }

    /**
     * Check if any part files exist.
     */
    public function hasExistingParts(): bool
    {
        return !empty($this->partFiles);
    }

    /**
     * Create a new part file and register it.
     */
    public function createPart(int $index): string
    {
        $partPath = $this->basePath.'.part'.$index;
        $this->partFiles[$index] = $partPath;
        ksort($this->partFiles);

        return $partPath;
    }

    /**
     * Get all part file paths in order.
     *
     * @return string[]
     */
    public function getPartFiles(): array
    {
        return array_values($this->partFiles);
    }

    /**
     * Merge all part files into the final file.
     */
    public function finalize(string $targetPath): void
    {
        if (empty($this->partFiles)) {
            return;
        }

        Utils::ensureDirectory(\dirname($targetPath));

        if (1 === \count($this->partFiles)) {
            $singlePart = reset($this->partFiles);
            if (!rename($singlePart, $targetPath)) {
                throw new \RuntimeException("Failed to rename temporary file {$singlePart} to {$targetPath}");
            }
            $this->partFiles = [];

            return;
        }

        $target = fopen($targetPath, 'w');
        if (false === $target) {
            throw new \RuntimeException("Cannot create target file: {$targetPath}");
        }

        try {
            foreach ($this->partFiles as $partFile) {
                if (!file_exists($partFile)) {
                    continue;
                }

                $source = fopen($partFile, 'r');
                if (false === $source) {
                    throw new \RuntimeException("Cannot read part file: {$partFile}");
                }

                try {
                    while (!feof($source)) {
                        $chunk = fread($source, 8192);
                        if (false !== $chunk) {
                            fwrite($target, $chunk);
                        }
                    }
                } finally {
                    fclose($source);
                }
            }
        } finally {
            fclose($target);
        }

        $this->cleanup();
    }

    /**
     * Delete all part files.
     */
    public function cleanup(): void
    {
        foreach ($this->partFiles as $partFile) {
            if (file_exists($partFile)) {
                @unlink($partFile);
            }
        }

        $this->partFiles = [];
    }

    /**
     * Validate that all parts are contiguous and complete.
     */
    public function validateParts(int $expectedTotalSize): bool
    {
        $totalSize = $this->getTotalDownloadedBytes();

        return $totalSize === $expectedTotalSize;
    }

    /**
     * Discover existing part files from previous interrupted downloads.
     */
    private function discoverExistingParts(): void
    {
        $pattern = $this->basePath.'.part*';
        $files = glob($pattern);

        if (false === $files) {
            return;
        }

        $this->partFiles = [];
        foreach ($files as $file) {
            if (preg_match('/\.part(\d+)$/', $file, $matches)) {
                $index = (int) $matches[1];
                $this->partFiles[$index] = $file;
            }
        }

        ksort($this->partFiles);
    }
}
