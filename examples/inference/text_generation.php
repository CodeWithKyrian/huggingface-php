<?php

declare(strict_types=1);

/**
 * Text Generation Example.
 *
 * Demonstrates text generation (completion) with various parameters.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;

$hf = HuggingFace::client();

// echo "=== Basic Text Generation ===\n";
// $generator = $hf->inference(InferenceProvider::HfInference)->textGeneration('katanemo/Arch-Router-1.5B');

// $response = $generator
//     ->maxNewTokens(50)
//     ->execute('The future of artificial intelligence is');

// echo "Generated: " . $response->generatedText . "\n\n";

echo "=== With Temperature and Top-P ===\n";
$generator = $hf->inference(InferenceProvider::HfInference)->textGeneration('HuggingFaceTB/SmolLM3-3B');

$response = $generator
    ->maxNewTokens(100)
    ->temperature(0.9)
    ->topP(0.95)
    ->topK(50)
    ->execute('Once upon a time in a galaxy far away,');

echo 'Generated: '.$response->generatedText."\n\n";

echo "=== Deterministic (No Sampling) ===\n";
$response = $generator
    ->maxNewTokens(20)
    ->doSample(false)
    ->execute('The capital of France is');

echo 'Generated: '.$response->generatedText."\n\n";

echo "=== With Stop Sequences ===\n";
$response = $generator
    ->maxNewTokens(50)
    ->stop(['.', "\n"])
    ->execute('List three fruits: apple,');

echo 'Generated: '.$response->generatedText."\n";
