<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\Hub\Exceptions\ApiException;
use Codewithkyrian\HuggingFace\HuggingFace;
use Codewithkyrian\HuggingFace\Support\Utils;

$hf = HuggingFace::client();

$outputDir = __DIR__.'/downloads';
Utils::ensureDirectory($outputDir);

try {
    echo "Testing Downloads...\n\n";

    echo "1. Downloading config.json (Simple)...\n";
    $path = $hf->hub()->repo('openai-community/gpt2')
        ->download('config.json')
        ->save($outputDir);
    echo "✓ Saved to: {$path}\n\n";

    echo "2. Downloading tokenizer.json (Resumable + Progress)...\n";
    $path = $hf->hub()->repo('openai-community/gpt2')
        ->download('tokenizer.json')
        ->resumable()
        ->onProgress(static function ($downloaded, $total) {
            $percent = $total > 0 ? ($downloaded / $total) * 100 : 0;
            echo sprintf(
                "\r   Downloading: %5.1f%% (%s / %s)",
                $percent,
                Utils::formatBytes($downloaded),
                Utils::formatBytes($total)
            );
        })
        ->save($outputDir);
    echo "\n✓ Saved to: {$path}\n\n";

    echo "3. Getting file info (No Download)...\n";
    $info = $hf->hub()->repo('openai-community/gpt2')
        ->download('pytorch_model.bin')
        ->info();

    echo "   File: pytorch_model.bin\n";
    echo '   Size: '.$info->humanSize()."\n";
    echo '   ETag: '.$info->etag."\n";
    echo "✓ Info retrieved successfully\n\n";

    echo "4. Downloading content to memory...\n";
    $content = $hf->hub()->repo('bert-base-uncased')
        ->download('vocab.txt')
        ->getContent();
    echo '   First 50 chars: '.substr($content, 0, 50)."...\n";
    echo "✓ Content retrieved\n\n";

    echo "5. Downloading and parsing JSON...\n";
    $data = $hf->hub()->repo('bert-base-uncased')
        ->download('config.json')
        ->json();
    echo '   Model type: '.$data['model_type']."\n";
    echo '   Architectures: '.implode(', ', $data['architectures'])."\n";
    echo "✓ JSON parsed successfully\n\n";
} catch (ApiException $e) {
    echo 'Error: '.$e->getMessage()."\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
