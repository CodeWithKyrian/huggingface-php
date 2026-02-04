<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\DTOs\PathInfo;

it('checks file existence in a repo', function (): void {
    $hf = hf_real_client();

    $revision = 'dd4bc8b21efa05ec961e3efc4ee5e3832a3679c7';
    $repo = $hf->hub()
        ->repo('google-bert/bert-base-uncased')
        ->revision($revision);

    expect($repo->fileExists('tf_model.h5'))->toBeTrue();
    expect($repo->fileExists('tf_model.h5dadazdzazd'))->toBeFalse();
});

it('gets path info for a public model repo', function (): void {
    $hf = hf_real_client();

    $revision = 'dd4bc8b21efa05ec961e3efc4ee5e3832a3679c7';
    $repo = $hf->hub()
        ->repo('google-bert/bert-base-uncased')
        ->revision($revision);

    $infos = $repo->pathsInfo(['tf_model.h5', 'config.json']);

    expect($infos)->toHaveCount(2);
    expect($infos[0])->toBeInstanceOf(PathInfo::class);

    $tfInfo = 'tf_model.h5' === $infos[0]->path ? $infos[0] : $infos[1];
    $configInfo = 'config.json' === $infos[0]->path ? $infos[0] : $infos[1];

    expect($tfInfo->isFile())->toBeTrue();
    expect($tfInfo->lfs)->not()->toBeNull();
    expect($tfInfo->lfs->oid)->toBe('a7a17d6d844b5de815ccab5f42cad6d24496db3850a2a43d8258221018ce87d2');
    expect($tfInfo->lfs->size)->toBe(536063208);
    expect($tfInfo->lfs->pointerSize)->toBe(134);

    expect($configInfo->isFile())->toBeTrue();
    expect($configInfo->lfs)->toBeNull();
});

it('lists files in a public model repo (including recursive)', function (): void {
    $hf = hf_real_client();

    $revision = 'dd4bc8b21efa05ec961e3efc4ee5e3832a3679c7';
    $repo = $hf->hub()
        ->repo('google-bert/bert-base-uncased')
        ->revision($revision);

    $files = iterator_to_array($repo->files(recursive: false));

    expect($files)->not()->toBeEmpty();
    expect($files[0])->toBeInstanceOf(PathInfo::class);

    $paths = array_map(static fn (PathInfo $info) => $info->path, $files);
    expect($paths)->toContain('.gitattributes', 'config.json');
});
