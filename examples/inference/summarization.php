<?php

declare(strict_types=1);

/**
 * Summarization Example.
 *
 * Demonstrates text summarization with various parameters.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();
$summarizer = $hf->inference()->summarization('facebook/bart-large-cnn');

$longText = <<<'TEXT'
The Tower of London, officially Her Majesty's Royal Palace and Fortress of the Tower of London, 
is a historic castle on the north bank of the River Thames in central London. It lies within the 
London Borough of Tower Hamlets, separated from the eastern edge of the square mile of the City 
of London by the open space known as Tower Hill. It was founded towards the end of 1066 as part 
of the Norman Conquest of England. The White Tower, which gives the entire castle its name, was 
built by William the Conqueror in 1078 and was a resented symbol of oppression, inflicted upon 
London by the new ruling elite. The castle was used as a prison from 1100 until 1952, although 
that was not its primary purpose. A grand palace early in its history, it served as a royal 
residence. As a whole, the Tower is a complex of several buildings set within two concentric 
rings of defensive walls and a moat. There were several phases of expansion, mainly under Kings 
Richard I, Henry III, and Edward I in the 12th and 13th centuries.
TEXT;

echo "=== Basic Summarization ===\n";
$result = $summarizer->execute($longText);

echo 'Summary: '.$result->summaryText."\n\n";

echo "=== Short Summary ===\n";
$result = $summarizer
    ->maxLength(50)
    ->minLength(20)
    ->execute($longText);

echo 'Summary: '.$result->summaryText."\n\n";

echo "=== Detailed Summary with Sampling ===\n";
$result = $summarizer
    ->maxLength(150)
    ->minLength(80)
    ->doSample()
    ->temperature(0.7)
    ->execute($longText);

echo 'Summary: '.$result->summaryText."\n";
