<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;

it('checks if a public repo exists', function (): void {
    $hf = hf_real_client();

    $exists = $hf->hub()->repo('openai-community/gpt2', RepoType::Model)->exists();
    expect($exists)->toBeTrue();

    $notExists = $hf->hub()->repo('this-definitely-does-not-exist-12345/model', RepoType::Model)->exists();
    expect($notExists)->toBeFalse();
});

it('checks if a repo exists after creating it', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();
    $repoId = sprintf('%s/EXISTS-%s', $who->name, bin2hex(random_bytes(4)));

    $manager = $hf->hub()->createRepo($repoId, RepoType::Model)
        ->public()
        ->save();

    try {
        expect($manager->exists())->toBeTrue();
    } finally {
        $manager->delete(missingOk: true);
        expect($manager->exists())->toBeFalse();
    }
});
