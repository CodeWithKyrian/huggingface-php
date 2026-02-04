<?php

declare(strict_types=1);

/**
 * Hub Search Example.
 *
 * Demonstrates how to search for models and datasets on the Hugging Face Hub,
 * and download model files.
 *
 * Usage: HF_TOKEN=your_token php examples/search.php
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\Hub\Enums\SortField;
use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();

echo "1. Search Models by Keyword\n";
echo str_repeat('-', 50)."\n";

$models = $hf->hub()->models()
    ->search('sentiment')
    ->limit(5)
    ->get();

echo "Top 5 models for 'sentiment':\n\n";

foreach ($models as $model) {
    echo "  📦 {$model->id}\n";
    echo '     Downloads: '.number_format($model->downloads)."\n";
    echo "     Likes: {$model->likes}\n";
    echo "     Task: {$model->pipelineTag}\n\n";
}

echo "2. Filter by Task and Library\n";
echo str_repeat('-', 50)."\n";

$models = $hf->hub()->models()
    ->task('text-classification')
    ->library('transformers')
    ->language('en')
    ->sort(SortField::Downloads)
    ->descending()
    ->limit(5)
    ->get();

echo "Top 5 English text-classification models (Transformers):\n\n";

foreach ($models as $model) {
    echo "  {$model->id} - ".number_format($model->downloads)." downloads\n";
}
echo "\n";

echo "3. Search by Author\n";
echo str_repeat('-', 50)."\n";

$models = $hf->hub()->models()
    ->author('sentence-transformers')
    ->sort(SortField::Likes)
    ->descending()
    ->limit(5)
    ->get();

echo "Top 5 Sentence Transformers models by likes:\n\n";

foreach ($models as $model) {
    echo "  ⭐ {$model->likes} - {$model->id}\n";
}
echo "\n";

echo "4. Search Datasets\n";
echo str_repeat('-', 50)."\n";

$datasets = $hf->hub()->datasets()
    ->search('summarization')
    ->limit(5)
    ->get();

echo "Top 5 summarization datasets:\n\n";

foreach ($datasets as $dataset) {
    echo "  📊 {$dataset->id}\n";
    echo '     Downloads: '.number_format($dataset->downloads)."\n\n";
}

echo "5. Get Model Details\n";
echo str_repeat('-', 50)."\n";

$modelId = 'distilbert-base-uncased-finetuned-sst-2-english';

$repo = $hf->hub()->repo($modelId)->info();

echo "Model: {$repo->id}\n";
echo "Author: {$repo->author}\n";
echo 'Downloads: '.number_format($repo->downloads)."\n";
echo 'Private: '.($repo->private ? 'Yes' : 'No')."\n";
echo 'Tags: '.implode(', ', $repo->tags ?? [])."\n\n";

echo "6. List Model Files\n";
echo str_repeat('-', 50)."\n";

$files = $hf->hub()->repo($modelId)->files();

echo "Files in {$modelId}:\n\n";

foreach ($files as $file) {
    $size = $file->size > 1024 * 1024
        ? number_format($file->size / 1024 / 1024, 1).' MB'
        : number_format($file->size / 1024, 1).' KB';

    echo "  📄 {$file->path} ({$size})\n";
}
echo "\n";

echo "7. Pagination\n";
echo str_repeat('-', 50)."\n";

echo "Iterating through all BERT models (first 10):\n\n";

$count = 0;
foreach ($hf->hub()->models()->search('bert')->get() as $model) {
    echo "  {$model->id}\n";
    if (++$count >= 10) {
        echo "  ... and more\n";

        break;
    }
}
echo "\n";
