<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;

it('creates and deletes branches', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();
    $repoId = sprintf('%s/BRANCH-%s', $who->name, bin2hex(random_bytes(4)));

    $manager = $hf->hub()->createRepo($repoId, RepoType::Model)
        ->public()
        ->save();

    try {
        $manager->uploadFile('file.txt', 'file content', 'Add file');

        $manager->createBranch('new-branch');

        $branches = $manager->branches();
        $branchNames = array_map(static fn ($b) => $b->name, $branches);
        expect($branchNames)->toContain('new-branch');

        $content = $manager->revision('new-branch')
            ->download('file.txt')
            ->getContent();
        expect($content)->toBe('file content');

        $manager->createBranch('empty-branch', empty: true);

        expect($manager->revision('empty-branch')->fileExists('file.txt'))->toBeFalse();

        $manager->deleteBranch('new-branch');
        $manager->deleteBranch('empty-branch');

        $branchesAfter = $manager->branches();
        $branchNamesAfter = array_map(static fn ($b) => $b->name, $branchesAfter);
        expect($branchNamesAfter)->not()->toContain('new-branch');
        expect($branchNamesAfter)->not()->toContain('empty-branch');
    } finally {
        $manager->delete(missingOk: true);
    }
});
