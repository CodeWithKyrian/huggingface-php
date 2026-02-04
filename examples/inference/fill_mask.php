<?php

declare(strict_types=1);

/**
 * Fill Mask Example.
 *
 * Demonstrates filling in masked tokens in text (like BERT).
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();
$mask = $hf->inference()->fillMask('google-bert/bert-base-uncased');

echo "=== Fill Mask ===\n";
$results = $mask->execute('Paris is the [MASK] of France.');

echo "Input: Paris is the [MASK] of France.\n";
echo "Top predictions:\n";
foreach (array_slice($results, 0, 5) as $result) {
    $percentage = round($result->score * 100, 2);
    echo "  - \"{$result->tokenStr}\": {$percentage}%\n";
    echo "    Full: {$result->sequence}\n";
}
echo "\n";

echo "=== Another Example ===\n";
$results = $mask->execute('The quick brown [MASK] jumps over the lazy dog.');

echo "Input: The quick brown [MASK] jumps over the lazy dog.\n";
echo "Top predictions:\n";
foreach (array_slice($results, 0, 5) as $result) {
    $percentage = round($result->score * 100, 2);
    echo "  - \"{$result->tokenStr}\": {$percentage}%\n";
}
echo "\n";

echo "=== Programming Context ===\n";
$results = $mask->execute('I love programming in [MASK].');

echo "Input: I love programming in [MASK].\n";
echo "Top predictions:\n";
foreach (array_slice($results, 0, 5) as $result) {
    $percentage = round($result->score * 100, 2);
    echo "  - \"{$result->tokenStr}\": {$percentage}%\n";
}
