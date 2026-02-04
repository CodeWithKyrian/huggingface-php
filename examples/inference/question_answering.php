<?php

declare(strict_types=1);

/**
 * Question Answering Example.
 *
 * Demonstrates extractive question answering based on context.
 * The model extracts answers directly from the provided context.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();
$qa = $hf->inference()->questionAnswering('deepset/roberta-base-squad2');

$context = <<<'TEXT'
PHP is a general-purpose scripting language geared towards web development. It was originally 
created by Danish-Canadian programmer Rasmus Lerdorf in 1993 and released in 1995. PHP originally 
stood for Personal Home Page, but it now stands for the recursive acronym PHP: Hypertext 
Preprocessor. PHP is used by approximately 77.4% of all websites whose server-side programming 
language is known. PHP code may be executed via command line, embedded into HTML code, or, most 
commonly, served in conjunction with a web server. The PHP interpreter is typically implemented 
as a module or as a CGI executable by a web server.
TEXT;

echo "Context:\n{$context}\n\n";

echo "=== Question 1 ===\n";
$result = $qa->execute('Who created PHP?', $context);

echo "Q: Who created PHP?\n";
echo "A: {$result->answer} (confidence: ".round($result->score, 4).")\n";
echo "   Position: {$result->start} - {$result->end}\n\n";

echo "=== Question 2 ===\n";
$result = $qa->execute('When was PHP released?', $context);

echo "Q: When was PHP released?\n";
echo "A: {$result->answer} (confidence: ".round($result->score, 4).")\n\n";

echo "=== Question 3 ===\n";
$result = $qa->execute('What does PHP stand for now?', $context);

echo "Q: What does PHP stand for now?\n";
echo "A: {$result->answer} (confidence: ".round($result->score, 4).")\n\n";

echo "=== Question 4 ===\n";
$result = $qa->execute('What percentage of websites use PHP?', $context);

echo "Q: What percentage of websites use PHP?\n";
echo "A: {$result->answer} (confidence: ".round($result->score, 4).")\n";
