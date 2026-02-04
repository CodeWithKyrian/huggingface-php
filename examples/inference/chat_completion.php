<?php

declare(strict_types=1);

/**
 * Chat Completion Example.
 *
 * Demonstrates conversational AI with the Inference API.
 * Supports both regular responses, streaming, and reasoning models.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();
$chat = $hf->inference()->chatCompletion('meta-llama/Llama-3.1-8B-Instruct');

echo "=== Basic Chat Completion ===\n";

$response = $chat
    ->system('You are a helpful assistant.')
    ->user('What is PHP?')
    ->maxTokens(300)
    ->temperature(0.7)
    ->generate();

$message = $response->choices[0]->message;
if ($message->hasReasoning()) {
    echo "Reasoning:\n".$message->reasoningContent."\n\n";
}

echo 'Response: '.$response->content()."\n";
echo 'Finish reason: '.$response->finishReason()."\n";
if ($response->usage) {
    echo 'Total tokens: '.$response->usage->totalTokens."\n";
}
echo "\n";

echo "=== Multi-turn Conversation ===\n";
$response = $chat
    ->system('You are a coding tutor.')
    ->user('What is a variable?')
    ->assistant('A variable is a named container that stores a value in memory.')
    ->user('Give me a PHP example.')
    ->maxTokens(300)
    ->generate();

if ($response->choices[0]->message->hasReasoning()) {
    echo "Reasoning:\n".$response->choices[0]->message->reasoningContent."\n\n";
}
echo 'Response: '.$response->content()."\n\n";

echo "=== Streaming Chat ===\n";
$stream = $chat
    ->system('You are a storyteller.')
    ->user('Tell me a very short story about a robot.')
    ->maxTokens(300)
    ->stream();

$hasShownReasoningHeader = false;
$hasShownResponseHeader = false;

foreach ($stream as $chunk) {
    if (empty($chunk->choices)) {
        continue;
    }

    $delta = $chunk->choices[0]->delta;

    if (null !== $delta->reasoningContent) {
        if (!$hasShownReasoningHeader) {
            echo 'Reasoning: ';
            $hasShownReasoningHeader = true;
        }
        echo $delta->reasoningContent;
    }

    if (null !== $delta->content) {
        if (!$hasShownResponseHeader) {
            if ($hasShownReasoningHeader) {
                echo "\n\n";
            }
            echo 'Response: ';
            $hasShownResponseHeader = true;
        }
        echo $delta->content;
    }
}
echo "\n";
