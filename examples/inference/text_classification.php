<?php

declare(strict_types=1);

/**
 * Text Classification Examples.
 *
 * Demonstrates various classification tasks:
 * - Sentiment analysis
 * - Zero-shot classification
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();

$classifier = $hf->inference()->textClassification('distilbert/distilbert-base-uncased-finetuned-sst-2-english');
echo "=== Sentiment Analysis ===\n";

$results = $classifier->execute('I absolutely love this product! Best purchase ever.');

foreach ($results as $result) {
    echo "Label: {$result->label}, Score: ".round($result->score, 4)."\n";
}
echo "\n";

echo "=== Zero-Shot Classification ===\n";
$zeroShot = $hf->inference()->zeroShotClassification('facebook/bart-large-mnli');

$results = $zeroShot->execute(
    'I need to book a flight to New York for next week.',
    ['travel', 'finance', 'technology', 'sports']
);

echo "Top predictions:\n";
foreach ($results as $result) {
    echo "  - {$result->label}: ".round($result->score, 4)."\n";
}
echo "\n";

echo "=== Multi-Label Classification ===\n";
$results = $zeroShot
    ->multiLabel()
    ->execute(
        'The new iPhone has an amazing camera and great battery life.',
        ['technology', 'photography', 'mobile devices', 'entertainment']
    );

echo "Labels (multi-label mode):\n";
foreach ($results as $result) {
    $isRelevant = $result->score > 0.5 ? '✓' : ' ';
    echo "  [{$isRelevant}] {$result->label}: ".round($result->score, 4)."\n";
}
