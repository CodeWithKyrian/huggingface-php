<?php

declare(strict_types=1);

it('downloads a snapshot of a public model repo', function (): void {
    $cacheDir = create_temp_dir();
    $hf = hf_real_client($cacheDir);

    try {
        $snapshotPath = $hf->hub()
            ->repo('openai-community/gpt2')
            ->snapshot(allowPatterns: ['*.md', '*.txt']);

        expect(is_dir($snapshotPath))->toBeTrue();

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($snapshotPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        expect($files)->not()->toBeEmpty();

        $filePaths = array_map('basename', $files);
        expect($filePaths)->toContain('README.md');
        expect($filePaths)->toContain('merges.txt');

        foreach ($files as $filePath) {
            expect(file_exists($filePath))->toBeTrue();
            expect(is_readable($filePath))->toBeTrue();
        }
    } finally {
        cleanup_temp_dir($cacheDir);
    }
});

it('downloads snapshot with ignore patterns', function (): void {
    $cacheDir = create_temp_dir();
    $hf = hf_test_client($cacheDir);

    $who = $hf->hub()->whoami();
    $repoId = sprintf('%s/SNAPSHOT-%s', $who->name, bin2hex(random_bytes(4)));

    $manager = $hf->hub()
        ->createRepo($repoId)
        ->public()
        ->save();

    try {
        $manager->uploadFiles([
            'keep.txt' => 'keep this',
            'ignore.log' => 'ignore this',
            'also-keep.json' => '{"keep": true}',
        ], 'Add test files');

        try {
            $snapshotPath = $manager->snapshot(ignorePatterns: ['*.log']);

            $files = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($snapshotPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }

            $filePaths = array_map('basename', $files);
            expect($filePaths)->toContain('keep.txt');
            expect($filePaths)->toContain('also-keep.json');
            expect($filePaths)->not()->toContain('ignore.log');
        } finally {
            cleanup_temp_dir($cacheDir);
        }
    } finally {
        $manager->delete(missingOk: true);
    }
});
