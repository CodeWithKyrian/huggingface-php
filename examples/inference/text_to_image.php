<?php

declare(strict_types=1);

/**
 * Text to Image Example.
 *
 * Demonstrates generating images from text prompts.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;

$hf = HuggingFace::client();
$imageGen = $hf->inference(InferenceProvider::HfInference)->textToImage('black-forest-labs/FLUX.1-schnell');

$outputDir = __DIR__.'/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

echo "=== Basic Image Generation ===\n";
$imageData = $imageGen->execute('A serene lake surrounded by mountains at sunset, oil painting style');

$outputPath = $outputDir.'/generated_landscape.png';
file_put_contents($outputPath, $imageData);
echo "Generated image saved to: {$outputPath}\n\n";

echo "=== With Custom Parameters ===\n";
$imageData = $imageGen
    ->numInferenceSteps(30)
    ->guidanceScale(7.5)
    ->execute('A futuristic city with flying cars, cyberpunk style, neon lights');

$outputPath = $outputDir.'/generated_city.png';
file_put_contents($outputPath, $imageData);
echo "Generated image saved to: {$outputPath}\n\n";

echo "=== With Negative Prompt and Size ===\n";
$imageData = $imageGen
    ->negativePrompt('blurry, low quality, distorted, ugly')
    ->size(512, 512)
    ->numInferenceSteps(25)
    ->execute('A cute cat wearing a tiny hat, professional photography');

$outputPath = $outputDir.'/generated_cat.png';
file_put_contents($outputPath, $imageData);
echo "Generated image saved to: {$outputPath}\n\n";

echo "=== Using save() Method ===\n";
$savedPath = $imageGen
    ->seed(42)
    ->save('A robot playing chess in a park', $outputDir.'/generated_robot.png');

echo "Image saved directly to: {$savedPath}\n";
