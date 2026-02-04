<?php

declare(strict_types=1);

/**
 * Image Classification Example.
 *
 * Demonstrates classifying images into categories.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();
$classifier = $hf->inference()->imageClassification('google/vit-base-patch16-224');

echo "=== Image Classification from URL ===\n";
$imageUrl = 'https://huggingface.co/datasets/huggingface/documentation-images/resolve/main/cats.png';

$results = $classifier->execute($imageUrl);

echo "Image: {$imageUrl}\n";
echo "Top predictions:\n";
foreach (array_slice($results, 0, 5) as $result) {
    $percentage = round($result->score * 100, 2);
    echo "  - {$result->label}: {$percentage}%\n";
}
echo "\n";

echo "=== Image Classification from Local File ===\n";
$localImage = __DIR__.'/sample_image.jpg';

if (file_exists($localImage)) {
    $imageData = base64_encode(file_get_contents($localImage));

    $results = $classifier->execute($imageData);

    echo "Image: {$localImage}\n";
    echo "Top predictions:\n";
    foreach (array_slice($results, 0, 5) as $result) {
        $percentage = round($result->score * 100, 2);
        echo "  - {$result->label}: {$percentage}%\n";
    }
} else {
    echo "Note: Place a sample_image.jpg in this directory to test local file classification.\n";
}
