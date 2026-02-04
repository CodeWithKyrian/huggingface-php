<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Support;

use Codewithkyrian\HuggingFace\HuggingFace;

/**
 * Utility helper functions.
 */
final class Utils
{
    /**
     * Build a URL with query parameters.
     *
     * @param array<string, mixed> $query
     */
    public static function buildUrl(string $baseUrl, array $query = []): string
    {
        if (empty($query)) {
            return $baseUrl;
        }

        $parts = [];

        foreach ($query as $key => $value) {
            if (null === $value || '' === $value) {
                continue;
            }

            if (\is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = urlencode((string) $key).'='.urlencode((string) $item);
                }
            } else {
                $parts[] = urlencode((string) $key).'='.urlencode((string) $value);
            }
        }

        if (empty($parts)) {
            return $baseUrl;
        }

        $queryString = implode('&', $parts);

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return "{$baseUrl}{$separator}{$queryString}";
    }

    /**
     * Safely get an environment variable value.
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        return false !== $value ? $value : $default;
    }

    /**
     * Check if we're running in offline mode.
     */
    public static function isOfflineMode(): bool
    {
        return 'true' === self::env('HF_OFFLINE', 'false')
            || 'true' === self::env('TRANSFORMERS_OFFLINE', 'false');
    }

    /**
     * Get the user agent string for HTTP requests.
     */
    public static function userAgent(): string
    {
        return \sprintf(
            'huggingface-php/%s; PHP/%s; %s',
            HuggingFace::VERSION,
            \PHP_VERSION,
            \PHP_OS_FAMILY
        );
    }

    /**
     * Ensure a directory exists, creating it if necessary.
     *
     * @return null|string The directory path if successful, null otherwise
     */
    public static function ensureDirectory(string $path): ?string
    {
        if (is_dir($path)) {
            return $path;
        }

        if (@mkdir($path, 0755, true) && is_dir($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Sanitize a filename to prevent directory traversal attacks.
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove any directory traversal attempts
        $filename = str_replace(['../', '..\\', '..'], '', $filename);

        // Remove leading slashes
        return ltrim($filename, '/\\');
    }

    /**
     * Format bytes to a human-readable string.
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, \count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Parse the Link header from an HTTP response.
     *
     * @return array<string, string> Map of rel => url
     */
    public static function parseLinkHeader(string $linkHeader): array
    {
        $links = [];
        $parts = explode(',', $linkHeader);

        foreach ($parts as $part) {
            if (preg_match('/<([^>]+)>;\s*rel="([^"]+)"/', $part, $matches)) {
                $links[$matches[2]] = $matches[1];
            }
        }

        return $links;
    }

    /**
     * Check if path matches any of the glob patterns.
     *
     * @param string[] $patterns
     */
    public static function fileMatchesPatterns(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $path) || fnmatch($pattern, basename($path))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively clear a directory's contents (but keep the directory itself).
     */
    public static function clearDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    }
}
