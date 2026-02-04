<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Hub\Exceptions\NotFoundException;

it('checks access to a public repo without authentication', function (): void {
    $hf = hf_real_client();

    $hf->hub()->repo('openai-community/gpt2', RepoType::Model)->checkAccess();
})->throwsNoExceptions();

it('throws NotFoundException when checking access to non-existent repo', function (): void {
    $hf = hf_test_client();

    $hf->hub()->repo('i--d/dont-exist', RepoType::Model)->checkAccess();
})->throws(NotFoundException::class);

it('checks access to owned private repo', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();
    $repoId = sprintf('%s/ACCESS-%s', $who->name, bin2hex(random_bytes(4)));

    $manager = $hf->hub()->createRepo($repoId, RepoType::Model)
        ->private()
        ->save();

    try {
        $manager->checkAccess();
    } finally {
        $manager->delete(missingOk: true);
    }
})->throwsNoExceptions();
