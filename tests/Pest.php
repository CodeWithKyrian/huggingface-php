<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\HuggingFace;

/**
 * Create a Hugging Face client configured for the Hub CI environment.
 */
function hf_test_client(?string $cacheDir = null): HuggingFace
{
    $factory = HuggingFace::factory()
        ->withHubUrl('https://hub-ci.huggingface.co')
        ->withToken('hf_94wBhPGp6KrrTH3KDchhKpRxZwd6dmHWLL');

    if ($cacheDir) {
        $factory->withCacheDir($cacheDir);
    }

    return $factory->make();
}

/**
 * Create a Hugging Face client for public operations (no token, real Hub).
 * Use this for operations that don't require authentication, like listing
 * files from public repos, checking file existence, etc.
 */
function hf_real_client(?string $cacheDir = null): HuggingFace
{
    $factory = HuggingFace::factory()
        ->withHubUrl('https://huggingface.co');

    if ($cacheDir) {
        $factory->withCacheDir($cacheDir);
    }

    return $factory->make();
}

function create_temp_dir(): string
{
    $dir = sys_get_temp_dir().'/hf-test-'.bin2hex(random_bytes(4));
    mkdir($dir, 0755, true);

    return $dir;
}

/**
 * Recursively deletes a directory and all files inside.
 *
 * @param string $dir path to the directory
 */
function cleanup_temp_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $path = $file->getPathname();
        if ($file->isDir()) {
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
