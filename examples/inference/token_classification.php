<?php

declare(strict_types=1);

/**
 * Token Classification (NER) Example.
 *
 * Demonstrates NER with various parameters (aggregation strategy, ignore labels, etc).
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;
use Codewithkyrian\HuggingFace\Inference\Enums\AggregationStrategy;

$hf = HuggingFace::client();

$model = 'dbmdz/bert-large-cased-finetuned-conll03-english';
$text = 'My name is Sarah and I live in London.';

echo "=== 1. Basic Token Classification ===\n";
$ner = $hf->inference()->tokenClassification($model);
$entities = $ner->execute($text);

foreach ($entities as $entity) {
    echo "- {$entity->entityGroup}: \"{$entity->word}\" (score: ".round($entity->score, 4).")\n";
}
echo "\n";

echo "=== 2. With Aggregation Strategy (Simple) ===\n";
$entities = $ner
    ->aggregationStrategy(AggregationStrategy::Simple)
    ->execute($text);

foreach ($entities as $entity) {
    echo "- {$entity->entityGroup}: \"{$entity->word}\" (score: ".round($entity->score, 4).")\n";
}
echo "\n";

echo "=== 3. Ignoring Specific Labels ===\n";
$entities = $ner
    ->aggregationStrategy(AggregationStrategy::Simple)
    ->ignoreLabel('PER')
    ->execute($text);

foreach ($entities as $entity) {
    echo "- {$entity->entityGroup}: \"{$entity->word}\" (score: ".round($entity->score, 4).")\n";
}
echo "\n";

echo "=== 4. With Stride (Long Text) ===\n";
$longText = str_repeat('My name is Sarah and I live in London. ', 20); // Simulate long text
$entities = $ner
    ->aggregationStrategy(AggregationStrategy::Simple)
    ->stride(16) // Overlap between chunks
    ->execute($longText);

echo 'Found '.count($entities)." entities in long text.\n";
