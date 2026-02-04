<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Support;

/**
 * Resolves Hugging Face authentication token from various sources.
 *
 * Resolution order:
 * 1. Explicit token (if provided)
 * 2. HF_TOKEN environment variable
 * 3. HUGGING_FACE_HUB_TOKEN environment variable (legacy)
 * 4. Token file from Hugging Face CLI (~/.huggingface/token or ~/.cache/huggingface/token)
 */
final class TokenResolver
{
    private const TOKEN_ENV_VARS = [
        'HF_TOKEN',
        'HUGGING_FACE_HUB_TOKEN',
    ];

    /**
     * Resolve the token from available sources.
     */
    public static function resolve(?string $explicitToken = null): ?string
    {
        // Explicit token takes priority
        if (null !== $explicitToken && '' !== $explicitToken) {
            return $explicitToken;
        }

        // Environment variables
        foreach (self::TOKEN_ENV_VARS as $envVar) {
            $token = Utils::env($envVar);
            if (null !== $token && '' !== $token) {
                return $token;
            }
        }

        // Token file
        $filePath = self::findTokenFile();
        if (null !== $filePath && is_readable($filePath)) {
            $token = trim((string) file_get_contents($filePath));
            if ('' !== $token) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Check if a token is set (from any source).
     */
    public static function isAuthenticated(): bool
    {
        return null !== self::resolve();
    }

    /**
     * Validate token format (basic validation).
     */
    public static function isValidFormat(?string $token): bool
    {
        if (null === $token || '' === $token) {
            return false;
        }

        // HF tokens typically start with 'hf_' and are alphanumeric
        return 1 === preg_match('/^hf_[a-zA-Z0-9]+$/', $token);
    }

    /**
     * Find the token file path based on standard locations.
     */
    private static function findTokenFile(): ?string
    {
        $potentialPaths = self::getTokenFilePaths();

        foreach ($potentialPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get potential token file paths for the current OS.
     *
     * @return string[]
     */
    private static function getTokenFilePaths(): array
    {
        $paths = [];

        $hfHome = Utils::env('HF_HOME');
        if (null !== $hfHome) {
            $paths[] = $hfHome.\DIRECTORY_SEPARATOR.'token';
        }

        $home = self::getHomeDirectory();
        if (null !== $home) {
            // New location (~/.cache/huggingface/token)
            $cachePath = match (\PHP_OS_FAMILY) {
                'Windows' => Utils::env('LOCALAPPDATA') ?? $home.\DIRECTORY_SEPARATOR.'AppData'.\DIRECTORY_SEPARATOR.'Local',
                'Darwin' => $home.\DIRECTORY_SEPARATOR.'Library'.\DIRECTORY_SEPARATOR.'Caches',
                default => Utils::env('XDG_CACHE_HOME') ?? $home.\DIRECTORY_SEPARATOR.'.cache',
            };
            $paths[] = $cachePath.\DIRECTORY_SEPARATOR.'huggingface'.\DIRECTORY_SEPARATOR.'token';

            // Legacy location (~/.huggingface/token)
            $paths[] = $home.\DIRECTORY_SEPARATOR.'.huggingface'.\DIRECTORY_SEPARATOR.'token';
        }

        return $paths;
    }

    /**
     * Get the user's home directory.
     */
    private static function getHomeDirectory(): ?string
    {
        // Try HOME environment variable first (Unix-like systems)
        $home = Utils::env('HOME');
        if (null !== $home) {
            return $home;
        }

        // Windows specific
        if (\PHP_OS_FAMILY === 'Windows') {
            $userProfile = Utils::env('USERPROFILE');
            if (null !== $userProfile) {
                return $userProfile;
            }

            $homeDrive = Utils::env('HOMEDRIVE');
            $homePath = Utils::env('HOMEPATH');
            if (null !== $homeDrive && null !== $homePath) {
                return $homeDrive.$homePath;
            }
        }

        return null;
    }
}
