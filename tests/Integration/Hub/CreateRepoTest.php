<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;

it('creates, uses and deletes a model repo', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();

    $random = bin2hex(random_bytes(8));
    $repoId = sprintf('%s/TEST-%s', $who->name, $random);

    $repo = $hf->hub()->createRepo($repoId, RepoType::Model)
        ->public()
        ->save();

    $info = $repo->info();

    expect($info->id)->toBe($repoId);
    expect($info->private)->toBeFalse();

    $content = 'hello from huggingface-php';
    $repo->uploadFile('.gitattributes', $content, 'Add test gitattributes');

    $downloaded = $repo->download('.gitattributes')->save();

    expect($repo->isCached('.gitattributes'))->toBeTrue();
    expect($repo->getCachedPath('.gitattributes'))->toBe($downloaded);
    expect($repo->download('.gitattributes')->getContent())->toBe($content);

    $repo->delete(missingOk: true);

    expect($repo->exists())->toBeFalse();
});
