<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\DTOs\SpaceInfo;
use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Hub\Enums\SpaceSdk;

it('gets space info for a public space', function (): void {
    $hf = hf_real_client();

    $info = $hf->hub()->spaceInfo('huggingfacejs/client-side-oauth');

    expect($info)->toBeInstanceOf(SpaceInfo::class);
    expect($info->id)->toBe('huggingfacejs/client-side-oauth');
    expect($info->private)->toBeFalse();
    expect($info->sdk)->toBe(SpaceSdk::Static);
    expect($info->likes)->toBeGreaterThanOrEqual(0);
});

it('gets space info with author', function (): void {
    $hf = hf_real_client();

    $info = $hf->hub()
        ->repo('huggingfacejs/client-side-oauth', RepoType::Space)
        ->info(expand: ['author']);

    expect($info)->toBeInstanceOf(SpaceInfo::class);
    expect($info->id)->toBe('huggingfacejs/client-side-oauth');
    expect($info->author)->toBe('huggingfacejs');
    expect($info->private)->toBeFalse();
    if (null !== $info->sdk) {
        expect($info->sdk)->toBeInstanceOf(SpaceSdk::class);
    }
});

it('gets space info for a specific revision', function (): void {
    $hf = hf_real_client();

    $revision = 'e410a9ff348e6bed393b847711e793282d7c672e';

    $info = $hf->hub()
        ->repo('huggingfacejs/client-side-oauth', RepoType::Space)
        ->revision($revision)
        ->info(expand: ['sha']);

    expect($info)->toBeInstanceOf(SpaceInfo::class);
    expect($info->id)->toBe('huggingfacejs/client-side-oauth');
    expect($info->private)->toBeFalse();
    expect($info->sha)->toBe($revision);
});
