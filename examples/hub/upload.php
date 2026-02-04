<?php

declare(strict_types=1);

/**
 * Repository Upload Example.
 *
 * Demonstrates how to create repositories and upload files to the Hugging Face Hub.
 *
 * ⚠️  This example creates real repositories on your account.
 *     Make sure you have a write-enabled token.
 *
 * Usage: HF_TOKEN=your_token php examples/upload.php
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();

$repoName = 'example-model-'.date('Ymd-His');

echo "This example will create a repository: {$repoName}\n";
echo "Press Enter to continue or Ctrl+C to cancel...\n";
fgets(\STDIN);

echo "1. Creating Repository\n";
echo str_repeat('-', 50)."\n";

try {
    $repo = $hf->hub()->createRepo($repoName, RepoType::Model)
        ->private()
        ->save();

    $repoInfo = $repo->info();

    echo "✓ Created repository: {$repoInfo->fullName()}\n";
    echo "  URL: {$repoInfo->url()}\n\n";
} catch (Exception $e) {
    echo "✗ Failed to create repository: {$e->getMessage()}\n";

    exit(1);
}

echo "2. Upload Single File\n";
echo str_repeat('-', 50)."\n";

$configContent = json_encode([
    'model_type' => 'example',
    'hidden_size' => 768,
    'num_labels' => 2,
    'created_by' => 'huggingface-php',
], \JSON_PRETTY_PRINT);

$commit = $repo->uploadFile('config.json', $configContent, 'Add model config');

echo "✓ Uploaded config.json\n";
echo "  Commit: {$commit->commit->url}\n\n";

echo "3. Upload Multiple Files\n";
echo str_repeat('-', 50)."\n";

$readme = <<<MARKDOWN
---
license: mit
tags:
  - example
  - huggingface-php
---

# Example Model

This model was created using the Hugging Face PHP SDK.

## Usage

```php
use Codewithkyrian\\HuggingFace\\HuggingFace;

\$hf = HuggingFace::client();
\$repoInfo = \$hf->hub()->repo('username/{$repoName}')->info();
```
MARKDOWN;

$tokenizerConfig = json_encode([
    'tokenizer_class' => 'BertTokenizer',
    'model_max_length' => 512,
], \JSON_PRETTY_PRINT);

$commit = $repo->commit('Add model card and tokenizer config')
    ->addFile('README.md', $readme)
    ->addFile('tokenizer_config.json', $tokenizerConfig)
    ->push();

echo "✓ Uploaded 2 files in single commit\n";
echo "  Commit: {$commit->commit->url}\n\n";

echo "4. Verify Uploaded Files\n";
echo str_repeat('-', 50)."\n";

$files = $repo->files();

echo "Repository contents:\n";
foreach ($files as $file) {
    $size = $file->size > 1024
        ? number_format($file->size / 1024, 1).' KB'
        : $file->size.' bytes';
    echo "  📄 {$file->path} ({$size})\n";
}
echo "\n";

echo "5. Update a File\n";
echo str_repeat('-', 50)."\n";

$updatedConfig = json_encode([
    'model_type' => 'example',
    'hidden_size' => 768,
    'num_labels' => 3,  // Changed from 2 to 3
    'created_by' => 'huggingface-php',
    'updated_at' => date('c'),
], \JSON_PRETTY_PRINT);

$commit = $repo->uploadFile('config.json', $updatedConfig, 'Update config: add num_labels');

echo "✓ Updated config.json\n";
echo "  Commit: {$commit->commit->url}\n\n";

echo "6. Delete a File\n";
echo str_repeat('-', 50)."\n";

$repo->uploadFile('temp.txt', 'temporary file');
echo "  Added temp.txt\n";

$commit = $repo->deleteFile('temp.txt', 'Remove temporary file');
echo "✓ Deleted temp.txt\n";
echo "  Commit: {$commit->commit->url}\n\n";

echo "7. Cleanup\n";
echo str_repeat('-', 50)."\n";

echo "Repository URL: {$repoInfo->url()}\n\n";
echo 'Delete the repository? (y/N): ';
$answer = trim(fgets(\STDIN));

if ('y' === strtolower($answer)) {
    $repo->delete();
    echo "✓ Repository deleted\n";
} else {
    echo "Repository kept. You can delete it manually from the web UI.\n";
}

echo "\n=== Byee 👋🏽 ===\n";
