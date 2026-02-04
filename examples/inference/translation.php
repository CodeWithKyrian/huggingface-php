<?php

declare(strict_types=1);

/**
 * Translation Example.
 *
 * Demonstrates text translation between languages.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();

echo "=== English to French ===\n";
$translator = $hf->inference()->translation('Helsinki-NLP/opus-mt-en-fr');

$result = $translator->execute('Hello, how are you today? I hope you are doing well.');

echo "Original: Hello, how are you today? I hope you are doing well.\n";
echo "French: {$result->translationText}\n\n";

echo "=== English to German ===\n";
$translator = $hf->inference()->translation('Helsinki-NLP/opus-mt-en-de');

$result = $translator->execute('The weather is beautiful today. Let us go for a walk in the park.');

echo "Original: The weather is beautiful today. Let us go for a walk in the park.\n";
echo "German: {$result->translationText}\n\n";

echo "=== English to Spanish ===\n";
$translator = $hf->inference()->translation('Helsinki-NLP/opus-mt-en-es');

$result = $translator->execute('I love programming. Creating software is my passion.');

echo "Original: I love programming. Creating software is my passion.\n";
echo "Spanish: {$result->translationText}\n\n";

echo "=== French to English ===\n";
$translator = $hf->inference()->translation('Helsinki-NLP/opus-mt-fr-en');

$result = $translator->execute('Bonjour le monde! Comment allez-vous?');

echo "Original: Bonjour le monde! Comment allez-vous?\n";
echo "English: {$result->translationText}\n";
