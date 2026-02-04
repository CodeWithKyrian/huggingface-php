<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;

it('creates commits with multiple file operations', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();
    $repoId = sprintf('%s/COMMIT-%s', $who->name, bin2hex(random_bytes(4)));

    $manager = $hf->hub()->createRepo($repoId, RepoType::Model)
        ->public()
        ->save();

    try {
        $commit = $manager->commit('Add multiple files')
            ->addFile('test1.txt', 'content 1')
            ->addFile('test2.txt', 'content 2')
            ->push();

        expect($commit->commit->oid)->not()->toBeEmpty();

        expect($manager->fileExists('test1.txt'))->toBeTrue();
        expect($manager->fileExists('test2.txt'))->toBeTrue();

        $content1 = $manager->download('test1.txt')->getContent();
        expect($content1)->toBe('content 1');

        $manager->deleteFile('test1.txt', 'Remove test1');

        expect($manager->fileExists('test1.txt'))->toBeFalse();
        expect($manager->fileExists('test2.txt'))->toBeTrue();
    } finally {
        $manager->delete(missingOk: true);
    }
});

it('lists commits from a repo', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();
    $repoId = sprintf('%s/COMMITS-%s', $who->name, bin2hex(random_bytes(4)));

    $manager = $hf->hub()->createRepo($repoId, RepoType::Model)
        ->public()
        ->save();

    try {
        $manager->uploadFile('file1.txt', 'content 1', 'First commit');
        $manager->uploadFile('file2.txt', 'content 2', 'Second commit');

        $commits = iterator_to_array($manager->commits(10));
        expect($commits)->not()->toBeEmpty();
        expect(count($commits))->toBeGreaterThanOrEqual(2);

        $firstCommit = $commits[0];
        expect($firstCommit->id)->not()->toBeEmpty();
        expect($firstCommit->title)->toBeString();
    } finally {
        $manager->delete(missingOk: true);
    }
});

it('counts commits in a repo', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();
    $repoId = sprintf('%s/COUNT-%s', $who->name, bin2hex(random_bytes(4)));

    $manager = $hf->hub()->createRepo($repoId, RepoType::Model)
        ->public()
        ->save();

    try {
        $manager->uploadFile('file1.txt', 'content 1', 'Commit 1');
        $manager->uploadFile('file2.txt', 'content 2', 'Commit 2');
        $manager->uploadFile('file3.txt', 'content 3', 'Commit 3');

        $count = $manager->commitCount();
        expect($count)->toBeGreaterThanOrEqual(3);
    } finally {
        $manager->delete(missingOk: true);
    }
});
