<?php

declare(strict_types=1);

/**
 * Object Detection Example.
 *
 * Demonstrates object detection with threshold adjustments.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();
$model = 'facebook/detr-resnet-50';
$imageUrl = 'https://huggingface.co/datasets/huggingface/documentation-images/resolve/main/coco_sample.png';

$detector = $hf->inference()->objectDetection($model);

echo "=== 1. Basic Object Detection ===\n";
$objects = $detector->execute($imageUrl);

foreach ($objects as $obj) {
    echo "- {$obj->label}: ".round($obj->score, 4)."\n";
}
echo "\n";

echo "=== 2. Low Confidence Threshold (0.5) ===\n";
$objects = $detector
    ->threshold(0.5)
    ->execute($imageUrl);

echo "Detected objects with lower threshold:\n";
foreach ($objects as $obj) {
    echo "- {$obj->label}: ".round($obj->score, 4)."\n";
}
