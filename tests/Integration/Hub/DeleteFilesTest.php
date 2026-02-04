<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;

it('deletes multiple files in a single commit', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();
    $repoId = sprintf('%s/DELETE-%s', $who->name, bin2hex(random_bytes(4)));

    $manager = $hf->hub()->createRepo($repoId, RepoType::Model)
        ->public()
        ->save();

    try {
        $manager->uploadFiles([
            'file1.txt' => 'content 1',
            'file2.txt' => 'content 2',
            'file3.txt' => 'content 3',
            'keep.txt' => 'keep this',
        ], 'Add test files');

        expect($manager->fileExists('file1.txt'))->toBeTrue();
        expect($manager->fileExists('file2.txt'))->toBeTrue();
        expect($manager->fileExists('file3.txt'))->toBeTrue();
        expect($manager->fileExists('keep.txt'))->toBeTrue();

        $manager->deleteFiles(['file1.txt', 'file2.txt', 'file3.txt'], 'Remove test files');

        expect($manager->fileExists('file1.txt'))->toBeFalse();
        expect($manager->fileExists('file2.txt'))->toBeFalse();
        expect($manager->fileExists('file3.txt'))->toBeFalse();

        expect($manager->fileExists('keep.txt'))->toBeTrue();
    } finally {
        $manager->delete(missingOk: true);
    }
});
