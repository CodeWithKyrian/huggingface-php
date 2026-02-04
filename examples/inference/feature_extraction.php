<?php

declare(strict_types=1);

/**
 * Feature Extraction (Embeddings) Example.
 *
 * Demonstrates generating embeddings for text, useful for:
 * - Semantic search
 * - Clustering
 * - Classification
 * - Recommendation systems
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();
$embedder = $hf->inference()->featureExtraction('sentence-transformers/all-MiniLM-L6-v2');

echo "=== Single Text Embedding ===\n";
$embedding = $embedder->execute('Hello, how are you?');

echo 'Embedding dimensions: '.count($embedding)."\n";
echo 'First 5 values: '.implode(', ', array_map(static fn ($v) => round($v, 4), array_slice($embedding, 0, 5)))."...\n\n";

echo "=== Normalized Embedding ===\n";
$embedding = $embedder
    ->normalize()
    ->execute('Machine learning is fascinating.');

// Verify normalization (magnitude should be ~1.0)
$magnitude = sqrt(array_sum(array_map(static fn ($x) => $x * $x, $embedding)));
echo 'Magnitude (should be ~1.0): '.round($magnitude, 4)."\n\n";

echo "=== Batch Embeddings ===\n";
$texts = [
    'I love programming in PHP.',
    'Python is great for data science.',
    'JavaScript runs in the browser.',
];

$embeddings = $embedder
    ->normalize()
    ->truncate()
    ->execute($texts);

echo 'Generated '.count($embeddings)." embeddings\n";
foreach ($embeddings as $i => $emb) {
    echo "Text {$i}: ".count($emb)." dimensions\n";
}
echo "\n";

echo "=== Semantic Similarity ===\n";
function cosineSimilarity(array $a, array $b): float
{
    $dotProduct = array_sum(array_map(static fn ($x, $y) => $x * $y, $a, $b));
    $magnitudeA = sqrt(array_sum(array_map(static fn ($x) => $x * $x, $a)));
    $magnitudeB = sqrt(array_sum(array_map(static fn ($x) => $x * $x, $b)));

    return $dotProduct / ($magnitudeA * $magnitudeB);
}

$query = $embedder->normalize()->execute('I enjoy coding.');

echo "Query: 'I enjoy coding.'\n";
foreach ($texts as $i => $text) {
    $similarity = cosineSimilarity($query, $embeddings[$i]);
    echo "  vs '{$text}': ".round($similarity, 4)."\n";
}
