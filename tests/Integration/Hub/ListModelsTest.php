<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\DTOs\ModelInfo;
use Codewithkyrian\HuggingFace\Hub\Enums\SortField;

it('lists models by search query', function (): void {
    $hf = hf_real_client();

    $results = iterator_to_array(
        $hf->hub()->models()
            ->search('t5')
            ->limit(10)
            ->get()
    );

    expect($results)->toHaveCount(10);
    expect($results[0])->toBeInstanceOf(ModelInfo::class);
    expect(strtolower($results[0]->id))->toContain('t5');
});

it('lists models by task and author', function (): void {
    $hf = hf_real_client();

    $results = iterator_to_array(
        $hf->hub()->models()
            ->author('Intel')
            ->task('depth-estimation')
            ->limit(5)
            ->get()
    );

    expect($results)->not()->toBeEmpty();
    expect($results[0])->toBeInstanceOf(ModelInfo::class);
    expect($results[0]->author)->toBe('Intel');
    expect($results[0]->pipelineTag)->toBe('depth-estimation');
});

it('lists models with tags filter', function (): void {
    $hf = hf_real_client();

    $results = iterator_to_array(
        $hf->hub()->models()
            ->tag('gguf')
            ->tag('safetensors')
            ->limit(5)
            ->get()
    );

    expect($results)->not()->toBeEmpty();

    foreach ($results as $model) {
        expect($model->tags)->toBeArray();
        expect($model->tags)->toContain('gguf');
        expect($model->tags)->toContain('safetensors');
    }
});

it('sorts models by downloads', function (): void {
    $hf = hf_real_client();

    $results = iterator_to_array(
        $hf->hub()->models()
            ->search('bert')
            ->sort(SortField::Downloads)
            ->limit(5)
            ->get()
    );

    expect($results)->toHaveCount(5);
    // Should be sorted descending by default
    $downloads = array_map(static fn(ModelInfo $m) => $m->downloads, $results);
    $sorted = $downloads;
    rsort($sorted);
    expect($downloads)->toBe($sorted);
});
