<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\DTOs\DatasetInfo;
use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;

it('gets dataset info for a public dataset', function (): void {
    $hf = hf_real_client();

    $info = $hf->hub()->datasetInfo('nyu-mll/glue');

    expect($info)->toBeInstanceOf(DatasetInfo::class);
    expect($info->id)->toBe('nyu-mll/glue');
    expect($info->private)->toBeFalse();
    expect($info->downloads)->toBeGreaterThanOrEqual(0);
    expect($info->likes)->toBeGreaterThanOrEqual(0);
    expect($info->gated)->toBeBool();
});

it('gets dataset info with author', function (): void {
    $hf = hf_real_client();

    $info = $hf->hub()->repo('nyu-mll/glue', RepoType::Dataset)
        ->info(expand: ['author']);

    expect($info)->toBeInstanceOf(DatasetInfo::class);
    expect($info->id)->toBe('nyu-mll/glue');
    expect($info->author)->toBe('nyu-mll');
    expect($info->private)->toBeFalse();
});

it('gets dataset info for a specific revision', function (): void {
    $hf = hf_real_client();

    $revision = 'cb2099c76426ff97da7aa591cbd317d91fb5fcb7';

    $info = $hf->hub()->repo('nyu-mll/glue', RepoType::Dataset)
        ->revision($revision)
        ->info(expand: ['sha']);

    expect($info)->toBeInstanceOf(DatasetInfo::class);
    expect($info->id)->toBe('nyu-mll/glue');
    expect($info->private)->toBeFalse();
    expect($info->sha)->toBe($revision);
});
