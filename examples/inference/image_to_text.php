<?php

declare(strict_types=1);

/**
 * Image to Text (Image Captioning) Example.
 *
 * Demonstrates generating text descriptions from images.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();
$captioner = $hf->inference()->imageToText('Salesforce/blip-image-captioning-base');

echo "=== Image Captioning ===\n";
$imageUrl = 'https://huggingface.co/datasets/huggingface/documentation-images/resolve/main/cats.png';

$result = $captioner->execute($imageUrl);

echo "Image: {$imageUrl}\n";
echo "Caption: {$result->generatedText}\n\n";

echo "=== Another Image ===\n";
$imageUrl2 = 'https://huggingface.co/datasets/huggingface/documentation-images/resolve/main/transformers/tasks/car.jpg';

$result = $captioner->execute($imageUrl2);

echo "Image: {$imageUrl2}\n";
echo "Caption: {$result->generatedText}\n";
