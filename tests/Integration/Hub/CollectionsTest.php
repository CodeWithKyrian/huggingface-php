<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Exceptions\RateLimitException;
use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;

it('lists collections for the authenticated user (if any)', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();

    $results = iterator_to_array(
        $hf->hub()->collections()
            ->owner($who->name)
            ->limit(10)
            ->get()
    );

    if ([] !== $results) {
        expect($results[0]->owner->name)->toBe($who->name);
    } else {
        expect($results)->toBeArray();
    }
});

it('creates a collection, adds and removes an item, and finally deletes the collection', function (): void {
    $hf = hf_test_client();

    $who = $hf->hub()->whoami();

    $random = bin2hex(random_bytes(8));
    $title = sprintf('Test Collection %s', $random);

    try {
        $manager = $hf->hub()
            ->createCollection($title, $who->name)
            ->description('This is a test collection from huggingface-php')
            ->public()
            ->save();
    } catch (RateLimitException $e) {
        test()->markTestSkipped($e->getMessage());
    }

    $collection = $manager->info();

    expect($collection->owner->name)->toBe($who->name);
    expect($collection->title)->toBe($title);

    $slug = $collection->slug;
    $manager = $hf->hub()->collection($slug);

    try {
        $manager->addItem($collection->slug, CollectionItemType::Collection, 'This is a test item');

        $info = $manager->info();
        expect($info->items)->toHaveCount(1);

        $item = $info->items[0];
        expect($item->type->value)->toBe('collection');
        expect($item->note['text'] ?? null)->toBeString()->toBe('This is a test item');

        // Remove the item again.
        $manager->deleteItem($item->id);

        $infoAfter = $manager->info();
        expect($infoAfter->items)->toBeArray();
    } finally {
        $manager->delete();
    }
});
