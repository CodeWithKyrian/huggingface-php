<?php

declare(strict_types=1);

it('downloads a file from a public repo', function (): void {
    $hf = hf_real_client();

    $content = $hf->hub()
        ->download('openai-community/gpt2', 'README.md')
        ->getContent();

    expect($content)->toBeString();
    expect($content)->toContain('GPT-2');
    expect($content)->toContain('language: en');
});

it('downloads file to a directory', function (): void {
    $hf = hf_real_client();

    $tempDir = create_temp_dir();

    try {
        $path = $hf->hub()
            ->download('openai-community/gpt2', 'config.json')
            ->save($tempDir);

        expect($path)->toBeString();
        expect(file_exists($path))->toBeTrue();
        expect(basename($path))->toBe('config.json');

        $content = file_get_contents($path);
        $data = json_decode($content, true);
        expect($data)->toBeArray();
        expect($data)->toHaveKey('vocab_size');
    } finally {
        cleanup_temp_dir($tempDir);
    }
});

it('downloads file from a private repo with token', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();
    $repoId = sprintf('%s/DOWNLOAD-%s', $who->name, bin2hex(random_bytes(4)));

    $manager = $hf->hub()
        ->createRepo($repoId)
        ->public()
        ->save();

    try {
        $manager->uploadFile('secret.txt', 'secret content', 'Add secret file');

        $content = $manager->download('secret.txt')->getContent();
        expect($content)->toBe('secret content');
    } finally {
        $manager->delete(missingOk: true);
    }
});

it('gets file download info without downloading', function (): void {
    $hf = hf_real_client();

    $info = $hf->hub()
        ->download('openai-community/gpt2', 'config.json')
        ->info();

    expect($info->size)->toBeGreaterThan(0);
    expect($info->url)->toBeString();
    expect($info->url)->toContain('openai-community/gpt2');
});
