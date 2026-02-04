<?php

declare(strict_types=1);

/**
 * Collections Example.
 *
 * Demonstrates how to create, manage, and delete collections on the Hugging Face Hub.
 *
 * Usage: HF_TOKEN=your_token php examples/collections.php
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\Hub\Enums\CollectionItemType;
use Codewithkyrian\HuggingFace\Hub\Enums\CollectionSortField;
use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();

// 0. Ensure we are authenticated
$me = $hf->hub()->whoami();
echo "Authenticated as: {$me->name}\n\n";

// 1. Create a Collection
echo "Creating collection 'php-sdk-test-collection'...\n";
$collectionName = 'php-sdk-test-collection-'.uniqid();

try {
    $collection = $hf->hub()->createCollection($collectionName)
        ->description('A collection created by the PHP SDK integration test')
        ->private()
        ->save();
} catch (Exception $e) {
    echo 'Error creating collection: '.$e->getMessage()."\n";

    exit(1);
}

$info = $collection->info();
echo "Collection created: {$info->title} ({$info->slug})\n";
echo "URL: {$info->url}\n\n";

// 2. Add items to the collection
echo "Adding items...\n";

try {
    $collection->addItem('google-bert/bert-base-uncased', CollectionItemType::Model, 'Best model ever');
    $collection->addItem('openai-community/gpt2', CollectionItemType::Model, 'Another great model');
} catch (Exception $e) {
    echo 'Error adding items: '.$e->getMessage()."\n";
}

$info = $collection->info();
echo 'Items in collection: '.count($info->items)."\n";
foreach ($info->items as $item) {
    echo " - [{$item->type->value}] {$item->itemId()} (Note: ".($item->note['text'] ?? 'None').")\n";
}
echo "\n";

// 3. List collections
echo "Listing collections for user...\n";
$collections = $hf->hub()->collections()
    ->owner($me->name)
    ->sort(CollectionSortField::LastModified)
    ->limit(5)
    ->get();

foreach ($collections as $col) {
    echo " - {$col->title} ({$col->slug})\n";
}
echo "\n";

// 4. Delete an item
if (!empty($info->items)) {
    $itemToDelete = $info->items[0];
    echo "Deleting item {$itemToDelete->itemId()} (object id: {$itemToDelete->id})...\n";
    $collection->deleteItem($itemToDelete->id);

    $info = $collection->info();
    echo 'Items remaining: '.count($info->items)."\n\n";
}

// 5. Delete the collection
echo "Collection URL: {$info->url}\n\n";
echo 'Delete the collection? (y/N): ';
$answer = trim(fgets(\STDIN));

if ('y' === strtolower($answer)) {
    echo "Deleting collection...\n";
    $collection->delete();
    echo "Collection deleted.\n";
} else {
    echo "Collection kept. You can delete it manually from the web UI.\n";
}

echo "\n=== Byee 👋🏽 ===\n";
