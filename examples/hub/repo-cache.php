<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

/**
 * Test script for the unified repo cache system.
 *
 * This script demonstrates:
 * - Single file downloads to cache
 * - Snapshot downloads with pattern filtering
 * - Cache checking and retrieval
 * - Blob deduplication
 *
 * Cache directory: examples/test-cache/
 */
$cacheDir = __DIR__.'/test-cache';

echo "=== Hugging Face PHP Repo Cache Test ===\n\n";
echo "Cache directory: {$cacheDir}\n\n";

$hf = HuggingFace::factory()
    ->withCacheDir($cacheDir)
    ->make();

$repo = $hf->hub()->repo('openai-community/gpt2');

echo "1. Testing single file download to cache...\n";

try {
    $cachedPath = $repo->download('config.json')->saveToCache();
    echo "   ✓ Downloaded config.json to cache\n";
    echo "   Path: {$cachedPath}\n";
    echo '   Exists: '.(file_exists($cachedPath) ? 'Yes' : 'No')."\n";
    if (is_link($cachedPath)) {
        $target = readlink($cachedPath);
        echo "   Symlink target: {$target}\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}

echo "\n2. Testing cache check...\n";

try {
    $isCached = $repo->isCached('config.json');
    echo '   config.json cached: '.($isCached ? 'Yes' : 'No')."\n";

    $cachedPath = $repo->getCachedPath('config.json');
    if (null !== $cachedPath) {
        echo "   Cached path: {$cachedPath}\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}

echo "\n3. Testing download to custom directory (should use cache)...\n";

try {
    $targetDir = __DIR__.'/test-output';

    $localPath = $repo->download('config.json')->save($targetDir);
    echo "   ✓ Downloaded config.json to custom directory\n";
    echo "   Path: {$localPath}\n";
    echo '   Exists: '.(file_exists($localPath) ? 'Yes' : 'No')."\n";
} catch (Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}

echo "\n4. Testing snapshot download (markdown and text files only)...\n";

try {
    $snapshotPath = $repo->snapshot(
        cacheDir: $cacheDir,
        allowPatterns: ['*.md', '*.txt']
    );
    echo "   ✓ Snapshot downloaded\n";
    echo "   Snapshot path: {$snapshotPath}\n";

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($snapshotPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $fileCount = 0;
    foreach ($files as $file) {
        if ($file->isFile()) {
            ++$fileCount;
            $relativePath = str_replace($snapshotPath.\DIRECTORY_SEPARATOR, '', $file->getPathname());
            echo "   - {$relativePath}";
            if (is_link($file->getPathname())) {
                echo ' -> '.readlink($file->getPathname());
            }
            echo "\n";
        }
    }
    echo "   Total files: {$fileCount}\n";
} catch (Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}

echo "\n5. Testing blob deduplication (download same file again)...\n";

try {
    $cachedPath2 = $repo->download('config.json')->saveToCache();
    echo "   ✓ Downloaded config.json again\n";
    echo "   Path: {$cachedPath2}\n";
    echo '   Same path as before: '.($cachedPath === $cachedPath2 ? 'Yes' : 'No')."\n";
} catch (Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}

echo "\n6. Inspecting cache structure...\n";
$storageFolder = $cacheDir.'/models--openai-community--gpt2';
if (is_dir($storageFolder)) {
    echo "   Storage folder: {$storageFolder}\n";

    $blobsDir = $storageFolder.'/blobs';
    if (is_dir($blobsDir)) {
        $blobs = glob($blobsDir.'/*');
        $blobCount = count(array_filter($blobs, static fn ($f) => is_file($f)));
        echo "   Blobs: {$blobCount}\n";
    }

    $snapshotsDir = $storageFolder.'/snapshots';
    if (is_dir($snapshotsDir)) {
        $snapshots = glob($snapshotsDir.'/*');
        $snapshotCount = count(array_filter($snapshots, static fn ($f) => is_dir($f)));
        echo "   Snapshots: {$snapshotCount}\n";
    }

    $refsDir = $storageFolder.'/refs';
    if (is_dir($refsDir)) {
        $refs = glob($refsDir.'/*');
        $refCount = count(array_filter($refs, static fn ($f) => is_file($f)));
        echo "   Refs: {$refCount}\n";
        foreach ($refs as $ref) {
            if (is_file($ref)) {
                $sha = trim(file_get_contents($ref));
                echo '     '.basename($ref)." -> {$sha}\n";
            }
        }
    }
}

echo "\n7. Testing getContent() (should use cache)...\n";

try {
    $content = $repo->download('config.json')->getContent();
    $data = json_decode($content, true);
    echo "   ✓ Retrieved content from cache\n";
    echo '   Config keys: '.implode(', ', array_keys($data))."\n";
} catch (Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}

echo "\n8. Testing isCached() method...\n";

try {
    $isCached = $repo->download('config.json')->isCached();
    echo '   config.json cached: '.($isCached ? 'Yes' : 'No')."\n";

    $isCached2 = $repo->download('nonexistent.json')->isCached();
    echo '   nonexistent.json cached: '.($isCached2 ? 'Yes' : 'No')."\n";
} catch (Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}

echo "\n9. Testing revision-specific operations...\n";

try {
    $v1Repo = $repo->revision('main');
    $info = $v1Repo->info();
    echo "   ✓ Retrieved info for revision 'main'\n";
    echo "   SHA: {$info->sha}\n";

    $files = iterator_to_array($v1Repo->files(recursive: false));
    echo '   Files in root: '.count($files)."\n";
} catch (Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}

echo "\n=== Test Complete ===\n";
echo "\nCache structure:\n";
echo "{$cacheDir}/\n";
echo "└── models--openai-community--gpt2/\n";
echo "    ├── blobs/          (deduplicated file storage)\n";
echo "    ├── snapshots/      (snapshot pointers)\n";
echo "    └── refs/           (revision mappings)\n";
echo "\nYou can inspect the cache directory to see the blob storage and symlinks.\n";
