<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\DTOs\ModelInfo;

it('gets model info for a public model', function (): void {
    $hf = hf_real_client();

    $info = $hf->hub()->modelInfo('openai-community/gpt2');

    expect($info)->toBeInstanceOf(ModelInfo::class);
    expect($info->id)->toBe('openai-community/gpt2');
    expect($info->pipelineTag)->toBe('text-generation');
    expect($info->private)->toBeFalse();
    expect($info->downloads)->toBeGreaterThan(0);
});

it('gets model info with expand fields', function (): void {
    $hf = hf_real_client();

    $info = $hf->hub()->repo('openai-community/gpt2')
        ->info(expand: ['siblings', 'sha']);

    expect($info)->toBeInstanceOf(ModelInfo::class);
    expect($info->id)->toBe('openai-community/gpt2');
    expect($info->sha)->not()->toBeNull();
});

it('gets model info for a specific revision', function (): void {
    $hf = hf_real_client();

    $revision = 'f27b190eeac4c2302d24068eabf5e9d6044389ae';

    $info = $hf->hub()->repo('openai-community/gpt2')
        ->revision($revision)
        ->info(expand: ['sha']);

    expect($info)->toBeInstanceOf(ModelInfo::class);
    expect($info->id)->toBe('openai-community/gpt2');
    expect($info->private)->toBeFalse();
    expect($info->sha)->toBe($revision);
});
