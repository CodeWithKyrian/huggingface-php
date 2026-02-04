<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\DTOs\DatasetInfo;

it('lists datasets by owner', function (): void {
    $hf = hf_real_client();

    $results = iterator_to_array(
        $hf->hub()->datasets()
            ->author('hf-doc-build')
            ->limit(5)
            ->get()
    );

    expect($results)->not()->toBeEmpty();
    expect($results[0])->toBeInstanceOf(DatasetInfo::class);
    expect($results[0]->author)->toBe('hf-doc-build');
});

it('lists datasets with search query', function (): void {
    $hf = hf_real_client();

    $results = iterator_to_array(
        $hf->hub()->datasets()
            ->search('squad')
            ->limit(5)
            ->get()
    );

    expect($results)->not()->toBeEmpty();
    expect($results[0])->toBeInstanceOf(DatasetInfo::class);
    expect(strtolower($results[0]->id))->toContain('squad');
});
