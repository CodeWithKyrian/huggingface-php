# Hugging Face PHP

<p>
        <a href="https://github.com/codewithkyrian/huggingface-php/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/codewithkyrian/huggingface-php/tests.yml?branch=main&label=tests&style=flat-square"></a>
        <a href="https://packagist.org/packages/codewithkyrian/huggingface"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/codewithkyrian/huggingface?style=flat-square"></a>
        <a href="https://packagist.org/packages/codewithkyrian/huggingface"><img alt="Latest Version" src="https://img.shields.io/packagist/v/codewithkyrian/huggingface?style=flat-square"></a>
        <a href="https://packagist.org/packages/codewithkyrian/huggingface"><img alt="License" src="https://img.shields.io/github/license/codewithkyrian/huggingface-php?style=flat-square"></a>
    </p>

A comprehensive PHP client for the [Hugging Face Hub](https://huggingface.co). Access thousands of machine learning models, datasets, run inference, and more, all from your PHP application.

```php
use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();

// Download a model config
$config = $hf->hub()->repo('bert-base-uncased')
    ->download('config.json')
    ->json();

// List models
$models = $hf->hub()->models()
    ->search('sentiment')
    ->limit(5)
    ->get();

// Chat with an LLM
$response = $hf->inference()
    ->chatCompletion('meta-llama/Llama-3.1-8B-Instruct')
    ->system('You are a helpful assistant.')
    ->user('What is PHP?')
    ->generate();

// Generate embeddings
$embeddings = $hf->inference()
    ->featureExtraction('sentence-transformers/all-MiniLM-L6-v2')
    ->normalize()
    ->execute('Hello world');
```

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Hub API](#hub-api)
  - [Repository Basics](#repository-basics)
  - [Repository Info](#repository-info)
  - [Creating Repositories](#creating-repositories)
  - [Updating Repositories](#updating-repositories)
  - [Repository Operations](#repository-operations)
  - [Branch Management](#branch-management)
  - [File Operations](#file-operations)
  - [Downloading Files](#downloading-files)
  - [Downloading Entire Repositories](#downloading-entire-repositories)
  - [Uploading Files](#uploading-files)
  - [Deleting Files](#deleting-files)
  - [Listing Commits](#listing-commits)
  - [Collections](#collections)
  - [Searching Models, Datasets, and Spaces](#searching-models-datasets-and-spaces)
- [Inference API](#inference-api)
  - [Provider Configuration](#provider-configuration)
  - [Chat Completion](#chat-completion)
  - [Text Generation](#text-generation)
  - [Feature Extraction (Embeddings)](#feature-extraction-embeddings)
  - [Text Classification](#text-classification)
  - [Token Classification](#token-classification)
  - [Summarization](#summarization)
  - [Question Answering](#question-answering)
  - [Translation](#translation)
  - [Fill Mask](#fill-mask)
  - [Sentence Similarity](#sentence-similarity)
  - [Text to Image](#text-to-image)
  - [Image Classification](#image-classification)
  - [Object Detection](#object-detection)
  - [Image to Text](#image-to-text)
  - [Text to Speech](#text-to-speech)
  - [Automatic Speech Recognition](#automatic-speech-recognition)
- [Caching](#caching)
- [Error Handling](#error-handling)
- [Examples](#examples)
- [API Reference](#api-reference)

## Installation

Install the package via Composer:

```bash
composer require codewithkyrian/huggingface
```

### Requirements

- PHP 8.2 or higher
- A PSR-18 HTTP client (e.g., Guzzle, Symfony HttpClient)

If you don't have a PSR-18 client installed, add Guzzle:

```bash
composer require guzzlehttp/guzzle
```

## Quick Start

The client works without authentication for public resources:

```php
<?php

require 'vendor/autoload.php';

use Codewithkyrian\HuggingFace\HuggingFace;

$hf = HuggingFace::client();

// Download a model file
$config = $hf->hub()
    ->repo('bert-base-uncased')
    ->download('config.json')
    ->json();

echo "Model type: {$config['model_type']}\n";

// List models
$models = $hf->hub()
    ->models()
    ->search('text-classification')
    ->library('transformers')
    ->limit(5)
    ->get();

foreach ($models->items as $model) {
    echo "{$model->id} - {$model->downloads} downloads\n";
}

// List files in a repository
$files = $hf->hub()->repo('gpt2')->files();

foreach ($files as $file) {
    echo "{$file->path} ({$file->size} bytes)\n";
}

// Work with specific revisions
$v1Repo = $hf->hub()->repo('gpt2')->revision('v1.0');
$info = $v1Repo->info();
$files = $v1Repo->files();
```

For operations requiring authentication (private repos, inference, uploads), provide a token:

```php
$hf = HuggingFace::client('hf_your_token');

// Run inference
$classifier = $hf->inference()->textClassification('distilbert-base-uncased-finetuned-sst-2-english');

$results = $classifier->execute('I love this product!');

echo $results[0]->label; // "POSITIVE"
```

## Configuration

### Authentication

A token is **optional** for public Hub operations (downloading, searching, listing). You need a token for:
- Accessing private repositories
- Running inference on most models
- Uploading files or creating repositories

**Getting a token:**

1. Create a free account at [huggingface.co](https://huggingface.co/join)
2. Go to [Settings → Access Tokens](https://huggingface.co/settings/tokens)
3. Create a token with appropriate permissions

### Basic Setup

```php
use Codewithkyrian\HuggingFace\HuggingFace;

// Without token (public operations only)
$hf = HuggingFace::client();

// With token
$hf = HuggingFace::client('hf_your_token_here');
```

### Environment Variables

The client automatically checks these environment variables:

```php
// Set HF_TOKEN or HUGGING_FACE_HUB_TOKEN in your environment
$hf = HuggingFace::client(); // Token loaded automatically
```

### Advanced Configuration

Use the factory for full control:

```php
$hf = HuggingFace::factory()
    ->withToken('hf_your_token')          // Optional
    ->withCacheDir('/path/to/cache')      // Custom cache directory
    ->withHubUrl('https://custom.hf')     // Custom Hub endpoint
    ->make();
```

## Hub API

The Hub API lets you manage repositories, upload models, and search the Hugging Face Hub.

### Repository Basics

Get a **RepoManager** for any repository. All operations (info, files, download, commit) flow through it.

```php
use Codewithkyrian\HuggingFace\Enums\RepoType;

$hub = $hf->hub();

$repo = $hub->repo('bert-base-uncased');                      // Model (default)
$repo = $hub->repo('squad', RepoType::Dataset);               // Dataset
$repo = $hub->repo('gradio/hello-world', RepoType::Space);    // Space
```

#### Working with Revisions

By default, operations use the `main` branch. Use `revision()` to target a specific branch, tag, or commit.

```php
$repo = $hub->repo('bert-base-uncased');

$v1Repo = $repo->revision('v1.0');
$info = $v1Repo->info();
$files = $v1Repo->files();
```

### Repository Info

Get metadata about a repository.

```php
$repo = $hub->repo('bert-base-uncased');

$info = $repo->info();
echo $info->id;        // "bert-base-uncased"
echo $info->downloads; // 12345678
```

Shorthand methods when you only need metadata:

```php
$modelInfo = $hub->modelInfo('bert-base-uncased');
$datasetInfo = $hub->datasetInfo('squad');
$spaceInfo = $hub->spaceInfo('gradio/hello-world');
```

### Creating Repositories

```php
use Codewithkyrian\HuggingFace\Enums\RepoType;
use Codewithkyrian\HuggingFace\Enums\SpaceSdk;

// Model repository
$repo = $hub->createRepo('my-model', RepoType::Model)
    ->private()
    ->license('mit')
    ->save();

// Dataset
$repo = $hub->createRepo('my-dataset', RepoType::Dataset)->save();

// Space
$repo = $hub->createRepo('my-space', RepoType::Space)
    ->sdk(SpaceSdk::Gradio, '4.0.0')
    ->save();
```

#### Create Options

| Method | Description |
|--------|-------------|
| `private()` | Make repository private |
| `license(string $id)` | Set license (e.g., `mit`, `apache-2.0`) |
| `sdk(SpaceSdk $sdk, string $version)` | Set Space SDK and version (for spaces only) |
| `hardware(SpaceHardware $hw)` | Set Space hardware tier (for spaces only) |

### Updating Repositories

```php
use Codewithkyrian\HuggingFace\Enums\Visibility;

$repo = $hub->repo('username/my-model');

$repo->setVisibility(Visibility::Public);

$repo->update([
    'description' => 'Updated description',
    'tags' => ['pytorch', 'text-classification'],
]);
```

### Repository Operations

```php
$repo = $hub->repo('username/my-model');

// Check existence
if ($repo->exists()) {
    echo "Repository exists";
}

// Check access
$repo->checkAccess(); // Throws AuthenticationException or ApiException if denied

// Rename/move
$renamedRepo = $repo->move('new-name');

// Fork
$forkedRepo = $repo->fork();
$forkedRepo = $repo->fork(targetNamespace: 'my-org');

// Delete
$repo->delete();
$repo->delete(missingOk: true); // Don't throw if not found
```

### Branch Management

```php
$repo = $hub->repo('username/my-model');

// Create branches
$repo->createBranch('feature-x');
$repo->createBranch('feature-y', revision: 'v1.0');
$repo->createBranch('empty-branch', empty: true);

// Delete branch
$repo->deleteBranch('old-branch');

// List branches and tags
$branches = $repo->branches();
$tags = $repo->tags();
$refs = $repo->refs(); // All refs (branches, tags, converts)
```

#### Deleting Repositories

```php
// Throws if repo is not found
$hub->repo('username/my-model')->delete();

// Don't throw if not found
$hub->repo('username/maybe-exists')->delete(missingOk: true);
```

### File Operations

#### Listing Files

```php
$repo = $hub->repo('bert-base-uncased');

$files = $repo->files();  // returns Generator

foreach ($files as $file) {
    echo "{$file->path} ({$file->size} bytes)\n";
}
```

#### Listing Options

| Method | Description |
|--------|-------------|
| `files(recursive: true)` | Include files in subdirectories |
| `files(expand: true)` | Include expanded metadata |
| `files(path: 'subdir')` | List files in specific directory |

#### File Information

```php
$repo = $hub->repo('bert-base-uncased');

// Check existence
$exists = $repo->fileExists('config.json');

// Single file info
$info = $repo->fileInfo('config.json');
echo $info->size;
echo $info->oid;
echo $info->isLfs();

// Multiple files
$filesInfo = $repo->pathsInfo(['config.json', 'model.safetensors']);
```

### Downloading Files

```php
$repo = $hub->repo('bert-base-uncased');

// Download to directory
$path = $repo->download('config.json')->save('/local/path');

// Download to cache (returns cached path)
$cachedPath = $repo->download('config.json')->save();

// Get content directly
$content = $repo->download('config.json')->getContent();

// Parse as JSON
$config = $repo->download('config.json')->json();

// Get metadata only
$info = $repo->download('config.json')->info();
echo "Size: {$info->size} bytes";
echo "ETag: {$info->etag}";
```

#### Download Options

| Method | Description |
|--------|-------------|
| `force()` | Re-download even if cached |
| `useCache(false)` | Skip cache entirely |
| `save(?string $path)` | Save to directory, or cache if null |
| `getContent()` | Get raw content as string |
| `json()` | Parse content as JSON |

#### Cache Helpers

```php
$repo = $hub->repo('bert-base-uncased');

$isCached = $repo->isCached('config.json');
$path = $repo->getCachedPath('config.json'); // null if not cached
```

### Downloading Entire Repositories

Download all files to a local cached snapshot.

```php
$repo = $hub->repo('bert-base-uncased');

$snapshotPath = $repo->snapshot();

// With filtering
$snapshotPath = $repo->snapshot(
    allowPatterns: ['*.json', '*.txt'],
    ignorePatterns: ['*.bin', '*.safetensors']
);

// Optimized (skip network check)
$snapshotPath = $repo->snapshot(force: false);
```

Snapshots are additive, so you can call `snapshot()` multiple times to build up a local content cache. Use `force: false` to skip the remote update check if you already have a cached revision.

### Uploading Files

#### Quick Upload

```php
$repo = $hub->repo('username/my-model');

// Single file
$repo->uploadFile('config.json', json_encode($config));
$repo->uploadFile('model.bin', '/local/path/model.bin');

// Multiple files
$repo->uploadFiles([
    'config.json' => json_encode($config),
    'model.bin' => '/local/path/model.bin',
]);
```

#### Commit Builder

For complex operations, use the commit builder:

```php
$repo->commit('Add model files')
    ->addFile('config.json', json_encode($config))
    ->addFile('model.bin', '/local/path/model.bin')
    ->addFile('readme.md', fopen('readme.md', 'r'))
    ->push();
```

`addFile()` accepts: **string** (content or path), **URL**, or **resource** (stream).

### Deleting Files

```php
$repo = $hub->repo('username/my-model');

// Single file
$repo->deleteFile('old-file.txt');

// Multiple files
$repo->deleteFiles(['file1.txt', 'file2.txt']);

// Combined with uploads
$repo->commit('Update files')
    ->addFile('new.json', $content)
    ->deleteFile('old.json')
    ->push();
```

### Listing Commits

```php
$repo = $hub->repo('bert-base-uncased');

$commits = $repo->commits(batchSize: 50);

foreach ($commits as $commit) {
    echo $commit->commit->title;
    echo $commit->commit->date;
}

$totalCommits = $repo->commitCount();
```

> [!NOTE]
> `commits()` returns a Generator that fetches pages lazily. The `batchSize` argument (1–1000) controls how many commits are fetched per API request; the generator keeps requesting more pages until all commits are returned, so use a `break` when you have enough results.

### Collections

Collections are curated lists of models, datasets, spaces, or papers.

#### Listing Collections

```php
$collections = $hub->collections()
    ->search('bert')
    ->owner('huggingface')
    ->limit(10)
    ->get();

foreach ($collections as $collection) {
    echo "{$collection->title} ({$collection->slug})\n";
}
```

#### Creating Collections

```php
$collection = $hub->createCollection('My Favorite Models')
    ->description('A curated list of awesome models')
    ->private()
    ->save();
```

#### Managing Items

```php
use Codewithkyrian\HuggingFace\Enums\CollectionItemType;

$collection = $hub->collection('my-collection-slug');

$info = $collection->info();

$collection->addItem('bert-base-uncased', CollectionItemType::Model, note: 'Great for NLP');
$collection->deleteItem('item-object-id');
$collection->delete();
```

### Searching Models, Datasets, and Spaces

Search returns a Generator that fetches results lazily.

#### Models

```php
use Codewithkyrian\HuggingFace\Enums\SortField;

$models = $hub->models()
    ->search('sentiment')
    ->task('text-classification')
    ->library('transformers')
    ->author('huggingface')
    ->language('en')
    ->sort(SortField::Downloads)
    ->descending()
    ->limit(20)
    ->get();

foreach ($models as $model) {
    echo "{$model->id}: {$model->downloads} downloads\n";
}
```

#### Model Search Options

| Method | Description |
|--------|-------------|
| `search(string $query)` | Full-text search |
| `task(string $task)` | Filter by pipeline task |
| `library(string $lib)` | Filter by library (e.g., `transformers`) |
| `author(string $author)` | Filter by author/organization |
| `language(string $lang)` | Filter by language code |
| `sort(SortField $field)` | Sort by downloads, likes, etc. |
| `descending()` | Sort in descending order |
| `limit(int $n)` | Maximum results to fetch |

#### Datasets

```php
$datasets = $hub->datasets()
    ->search('summarization')
    ->author('huggingface')
    ->limit(10)
    ->get();

foreach ($datasets as $dataset) {
    echo $dataset->id;
}
```

#### Spaces

```php
$spaces = $hub->spaces()
    ->search('gradio')
    ->limit(10)
    ->get();

foreach ($spaces as $space) {
    echo "{$space->id} ({$space->sdk})\n";
}
```

> [!WARNING]
> Without `limit()`, the generator fetches ALL matching results. Always set a limit or break manually.


## Inference API

The Inference API lets you run machine learning models on Hugging Face's infrastructure. It supports text generation, embeddings, classification, image generation, speech recognition, and more.

### Provider Configuration

The inference client supports multiple providers. Pass the provider directly to `inference()`:

```php
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;

// Default: Auto resolved
$inference = $hf->inference();

// External provider (enum)
$inference = $hf->inference(InferenceProvider::Together);

// External provider (string slug)
$inference = $hf->inference('groq');
$inference = $hf->inference('nebius');

// Custom endpoint URL
$inference = $hf->inference('https://your-endpoint.huggingface.cloud');
```

#### Supported Providers

| Provider | Slug | Tasks |
|----------|------|-------|
| Hugging Face | `hf-inference` (default) | All tasks |
| Black Forest Labs | `black-forest-labs` | Text-to-Image |
| Cerebras | `cerebras` | Chat |
| Cohere | `cohere` | Chat |
| Fal.ai | `fal-ai` | Text-to-Image, Text-to-Video, Image-to-Image, Image-to-Video, ASR, TTS |
| Featherless AI | `featherless-ai` | Chat, Text Generation |
| Fireworks AI | `fireworks-ai` | Chat |
| Groq | `groq` | Chat, Text Generation |
| Hyperbolic | `hyperbolic` | Chat, Text Generation, Text-to-Image |
| Nebius | `nebius` | Chat, Text Generation, Text-to-Image, Embeddings |
| Novita | `novita` | Chat, Text Generation |
| Nscale | `nscale` | Chat, Text-to-Image |
| OpenAI | `openai` | Chat (requires direct API key) |
| OVHcloud | `ovhcloud` | Chat, Text Generation |
| Replicate | `replicate` | Text-to-Image |
| Sambanova | `sambanova` | Chat, Embeddings |
| Scaleway | `scaleway` | Chat, Text Generation, Embeddings |
| Together AI | `together` | Chat, Text Generation, Text-to-Image |
| ZAI | `zai-org` | Chat, Text-to-Image |

#### Provider Resolution

When you don't specify a provider (or use `InferenceProvider::Auto`), the client automatically selects the best available provider for your model. Here's how it works:

1.  **Map Model Providers:** The client queries the Hugging Face Hub to find all providers that serve this model following the priority order of providers you've configured in your [Inference Provider settings](https://huggingface.co/settings/inference-providers) on Hugging Face.
2.  **Availability & Compatibility:** It selects the first provider that is **currently available** and **supports the requested task**.
3.  **Exception:** If no viable provider is found for the model and task, a `RoutingException` is thrown.

This ensures you always get the most reliable inference endpoint based on your personal or organization settings.

#### Billing

Bill requests to an organization:

```php
$chat = $hf->inference(InferenceProvider::Together)
    ->billTo('my-org-id')
    ->chatCompletion('meta-llama/Llama-3.1-8B-Instruct');
```

### Chat Completion

Chat with large language models using a conversational interface. Supports system prompts, multi-turn conversations, and streaming.

```php
$chat = $hf->inference()->chatCompletion('meta-llama/Llama-3.1-8B-Instruct');

$response = $chat
    ->system('You are a helpful assistant.')
    ->user('What is PHP?')
    ->maxTokens(200)
    ->generate();

echo $response->content();
echo $response->finishReason(); // "stop", "length", etc.
```

#### Multi-turn Conversations

```php
$response = $chat
    ->system('You are a coding tutor.')
    ->user('What is a variable?')
    ->assistant('A variable is a named container that stores a value.')
    ->user('Give me a PHP example.')
    ->generate();
```

#### Streaming

```php
$stream = $chat
    ->system('You are a storyteller.')
    ->user('Tell me a short story.')
    ->maxTokens(500)
    ->stream();

foreach ($stream as $chunk) {
    echo $chunk->choices[0]->delta->content ?? '';
}
```

#### Options

| Method | Description |
|--------|-------------|
| `system(string $content)` | Add a system message |
| `user(string $content)` | Add a user message |
| `assistant(string $content)` | Add an assistant message |
| `maxTokens(int $tokens)` | Maximum tokens to generate |
| `temperature(float $temp)` | Sampling temperature (0.0–2.0) |
| `topP(float $p)` | Nucleus sampling probability |
| `topK(int $k)` | Top-k sampling |
| `stop(array $sequences)` | Stop sequences |
| `seed(int $seed)` | Random seed for reproducibility |
| `frequencyPenalty(float $penalty)` | Reduce repetition of tokens |
| `presencePenalty(float $penalty)` | Encourage new topics |
| `logprobs(bool $b, ?int $k)` | Return log probabilities |
| `responseFormat(array $fmt)` | Set response format (e.g. JSON) |
| `tool(ChatCompletionTool $tool)` | Add a tool definition |
| `tools(array<ChatCompletionTool> $tools)` | Add multiple tools |
| `toolChoice(string\|array $c)` | Control tool choice |

### Text Generation

Generate text continuations from a prompt. Unlike chat completion, this is for raw text completion without conversation structure.

```php
$generator = $hf->inference()->textGeneration('gpt2');

$response = $generator
    ->maxNewTokens(100)
    ->temperature(0.7)
    ->execute('The future of AI is');

echo $response->generatedText;
```

#### Options

| Method | Description |
|--------|-------------|
| `maxNewTokens(int $n)` | Max new tokens to generate |
| `temperature(float $t)` | Sampling temperature |
| `topK(float $k)` | Top-k sampling |
| `topP(float $p)` | Nucleus sampling |
| `repetitionPenalty(float $p)` | Repetition penalty (> 1.0) |
| `doSample(bool $b)` | Enable/disable sampling |
| `returnFullText(bool $b)` | Include prompt in output |
| `seed(int $s)` | Random seed |
| `stop(string\|array $s)` | Stop sequence(s) |
| `truncate(int $t)` | Truncate inputs to size |
| `watermark(bool $b)` | Enable watermarking |
| `frequencyPenalty(float $p)` | Frequency penalty |
| `bestOf(int $n)` | Generate best of N sequences |
| `decoderInputDetails(bool $b)` | Return decoder input details |

*Also supports: `adapterId`, `details`, `grammar`, `topNTokens`, `typicalP`*

### Feature Extraction (Embeddings)

Generate vector embeddings for text. Useful for semantic search, clustering, and similarity comparisons.

```php
$embedder = $hf->inference()->featureExtraction('sentence-transformers/all-MiniLM-L6-v2');

// Single text
$embedding = $embedder->execute('Hello world');
echo count($embedding); // e.g., 384 dimensions

// Batch processing
$embeddings = $embedder->execute([
    'First sentence',
    'Second sentence',
    'Third sentence',
]);
```

#### Options

| Method | Description |
|--------|-------------|
| `normalize()` | Normalize embeddings to unit length |
| `truncate()` | Truncate input to model's max length |
| `promptName(string $name)` | Use a specific prompt template |
| `truncationDirection(TruncationDirection $direction)` | Left or Right truncation |

### Text Classification

Classify text into categories. Returns scored labels.

```php
$classifier = $hf->inference()->textClassification('distilbert-base-uncased-finetuned-sst-2-english');

$results = $classifier->execute('I absolutely love this product!');

foreach ($results as $result) {
    echo "{$result->label}: " . round($result->score * 100, 2) . "%\n";
}
// POSITIVE: 99.87%
// NEGATIVE: 0.13%
```

#### Options

| Method | Description |
|--------|-------------|
| `topK(int $k)` | Number of predictions to return |
| `functionToApply(ClassificationOutputTransform $f)` | Function to apply to scores (Sigmoid, Softmax, None) |

### Token Classification

Classify individual tokens in a text, such as identifying entities (NER) or parts of speech (POS).

```php
use Codewithkyrian\HuggingFace\Inference\Enums\AggregationStrategy;

$ner = $hf->inference()->tokenClassification('dbmdz/bert-large-cased-finetuned-conll03-english');

$results = $ner
    ->aggregationStrategy(AggregationStrategy::Simple)
    ->execute('My name is Sarah and I live in London');

foreach ($results as $result) {
    echo "{$result->word}: {$result->entityGroup} ({$result->score})\n";
}
// Sarah: PER (0.99)
// London: LOC (0.99)
```

#### Options

| Method | Description |
|--------|-------------|
| `aggregationStrategy(AggregationStrategy $s)` | Strategy to fuse tokens (None, Simple, First, Average, Max) |
| `ignoreLabels(array $labels)` | List of labels to ignore during classification |
| `stride(int $n)` | Overlap tokens between chunks for long text |

### Summarization

Summarize long text into shorter versions.

```php
$summarizer = $hf->inference()->summarization('facebook/bart-large-cnn');

$result = $summarizer
    ->maxLength(130)
    ->minLength(30)
    ->execute($longArticle);

echo $result->summaryText;
```

#### Options

| Method | Description |
|--------|-------------|
| `maxLength(int $length)` | Maximum summary length |
| `minLength(int $length)` | Minimum summary length |
| `doSample(bool $sample)` | Enable sampling for varied output |
| `temperature(float $temp)` | Sampling temperature |

### Question Answering

Extract answers from a context passage.

```php
$qa = $hf->inference()->questionAnswering('deepset/roberta-base-squad2');

$context = "PHP was created by Rasmus Lerdorf in 1994.";

$result = $qa->execute('Who created PHP?', $context);

echo $result->answer;  // "Rasmus Lerdorf"
echo $result->score;   // Confidence score
echo $result->start;   // Start position in context
echo $result->end;     // End position in context
```

#### Options

| Method | Description |
|--------|-------------|
| `topK(int $k)` | Number of answers to return |
| `docStride(int $n)` | Overlap size for long context chunks |
| `maxAnswerLen(int $n)` | Max answer length |
| `maxQuestionLen(int $n)` | Max question length |
| `maxSeqLen(int $n)` | Max chunk length (context + question) |
| `alignToWords(bool $b)` | Align answer to words (true by default) |
| `handleImpossibleAnswer(bool $b)` | Accept impossible answers |

### Translation

Translate text between languages. Model determines the language pair.

```php
$translator = $hf->inference()->translation('Helsinki-NLP/opus-mt-en-fr');

$result = $translator->execute('Hello, how are you?');

echo $result->translationText; // "Bonjour, comment allez-vous?"
```

#### Options

| Method | Description |
|--------|-------------|
| `srcLang(string $lang)` | Source language code |
| `tgtLang(string $lang)` | Target language code |
| `maxNewTokens(int $n)` | Max new tokens to generate |
| `temperature(float $t)` | Sampling temperature |
| `doSample(bool $b)` | Enable sampling |
| `cleanUpTokenizationSpaces(bool $b)` | Clean up spaces |
| `truncation(TruncationStrategy $s)` | Truncation strategy |

*Supports other generation parameters like `topK`, `topP`, etc.*

### Fill Mask

Predict masked tokens in text (like BERT's pre-training task).

```php
$mask = $hf->inference()->fillMask('bert-base-uncased');

$results = $mask->execute('Paris is the [MASK] of France.');

foreach ($results as $result) {
    echo "{$result->tokenStr}: " . round($result->score * 100, 2) . "%\n";
    echo "  → {$result->sequence}\n";
}
// capital: 85.42%
//   → paris is the capital of france.
```

#### Options

| Method | Description |
|--------|-------------|
| `topK(int $k)` | Number of predictions to return |
| `targets(array $targets)` | Limit predictions to specific words |

### Sentence Similarity

Compare a source sentence against multiple target sentences.

```php
$similarity = $hf->inference()->sentenceSimilarity('sentence-transformers/all-MiniLM-L6-v2');

$scores = $similarity->execute(
    'I love cats',
    ['I love dogs', 'I hate cats', 'The weather is nice']
);

// $scores = [0.92, 0.45, 0.12]
```

### Text to Image

Generate images from text prompts.

```php
$imageGen = $hf->inference()->textToImage('black-forest-labs/FLUX.1-schnell');

$imageData = $imageGen
    ->numInferenceSteps(20)
    ->guidanceScale(7.5)
    ->execute('A serene lake surrounded by mountains at sunset');

file_put_contents('output.png', $imageData);

// Or save directly
$imageGen->save('A beautiful sunset', 'sunset.png');
```

#### Options

| Method | Description |
|--------|-------------|
| `numInferenceSteps(int $steps)` | Number of denoising steps |
| `guidanceScale(float $scale)` | How closely to follow the prompt |
| `width(int $px)` | Output image width |
| `height(int $px)` | Output image height |
| `size(int $w, int $h)` | Set both width and height |
| `seed(int $seed)` | Random seed for reproducibility |
| `negativePrompt(string $prompt)` | What to avoid in the image |

### Image Classification

Classify images into categories.

```php
$classifier = $hf->inference()->imageClassification('google/vit-base-patch16-224');

// From URL
$results = $classifier->execute('https://example.com/cat.jpg');

// From base64
$results = $classifier->execute(base64_encode(file_get_contents('cat.jpg')));

foreach ($results as $result) {
    echo "{$result->label}: " . round($result->score * 100, 2) . "%\n";
}
```

#### Options

| Method | Description |
|--------|-------------|
| `topK(int $k)` | Number of predictions to return |
| `functionToApply(ClassificationOutputTransform $f)` | Function to apply to scores (Sigmoid, Softmax, None) |

### Object Detection

Detect objects in an image with bounding boxes.

```php
$detector = $hf->inference()->objectDetection('facebook/detr-resnet-50');

// From URL or file path
$results = $detector->execute('https://example.com/photo.jpg');

foreach ($results as $result) {
    echo "Label: {$result->label}, Score: " . round($result->score, 4) . "\n";
    echo "Box: [{$result->box->xmin}, {$result->box->ymin}, {$result->box->xmax}, {$result->box->ymax}]\n";
}
```

#### Options

| Method | Description |
|--------|-------------|
| `threshold(float $threshold)` | Probability threshold to make a prediction |

### Image to Text

Generate captions for images.

```php
$captioner = $hf->inference()->imageToText('Salesforce/blip-image-captioning-base');

$result = $captioner->execute('https://example.com/photo.jpg');

echo $result->generatedText; // "a dog playing in the park"
```

#### Options

| Method | Description |
|--------|-------------|
| `maxNewTokens(int $n)` | Max new tokens to generate |
| `temperature(float $t)` | Sampling temperature |
| `doSample(bool $b)` | Enable sampling |
| `topK(int $k)` | Top-k sampling |
| `topP(float $p)` | Nucleus sampling |
| `minNewTokens(int $n)` | Min new tokens to generate |
| `numBeams(int $n)` | Number of beams for beam search |

*Supports other standard generation parameters: `earlyStopping`, `numBeamGroups`, `penaltyAlpha`, `useCache`, `etaCutoff`, `epsilonCutoff`, `typicalP`*

### Text to Speech

Convert text to audio.

```php
$tts = $hf->inference()->textToSpeech('espnet/kan-bayashi_ljspeech_vits');

$audioData = $tts->execute('Hello, how are you today?');

file_put_contents('output.wav', $audioData);

// Or save directly
$tts->save('Hello world', 'greeting.wav');
```

#### Options

| Method | Description |
|--------|-------------|
| `maxNewTokens(int $n)` | Max new tokens to generate |
| `temperature(float $t)` | Sampling temperature |
| `doSample(bool $b)` | Enable sampling |
| `topK(int $k)` | Top-k sampling |
| `topP(float $p)` | Nucleus sampling |
| `minNewTokens(int $n)` | Min new tokens to generate |
| `numBeams(int $n)` | Number of beams for beam search |

*Supports other standard generation parameters: `earlyStopping`, `numBeamGroups`, `penaltyAlpha`, `useCache`, `etaCutoff`, `epsilonCutoff`, `typicalP`*

### Automatic Speech Recognition

Transcribe audio to text.

```php
$transcriber = $hf->inference()->automaticSpeechRecognition('openai/whisper-large-v3');

// From file path
$result = $transcriber->execute('/path/to/audio.mp3');

// From base64 data
$result = $transcriber->execute('data:audio/mpeg;base64,...');

echo $result->text;
```

### Zero-Shot Classification

Classify text into arbitrary categories without training.

```php
$zeroShot = $hf->inference()->zeroShotClassification('facebook/bart-large-mnli');

$results = $zeroShot->execute(
    'I need to book a flight to New York',
    ['travel', 'finance', 'technology', 'sports']
);

foreach ($results as $result) {
    echo "{$result->label}: " . round($result->score * 100, 2) . "%\n";
}
```

#### Options

| Method | Description |
|--------|-------------|
| `multiLabel(bool $enable)` | Allow multiple labels to be true |
| `hypothesisTemplate(string $template)` | Custom hypothesis template |

## Caching

The library uses a unified repository-based cache system with blob deduplication. This means:
- Files are stored once by their content hash (blob storage)
- Multiple snapshots share the same blob files via symlinks
- Files are only re-downloaded when they change on the server
- Repeated downloads are instant
- Zero-latency file checks using local manifests
- Your internet connection isn't required for cached files

### Cache Structure

```
<cacheDir>/
└── models--openai-community--gpt2/
    ├── blobs/              # Deduplicated file storage (by content hash)
    ├── snapshots/          # Snapshot pointers (symlinks to blobs)
    │   └── <commitSha>/
    │       └── config.json -> ../../../blobs/<hash>
    └── refs/               # Revision mappings (branch/tag -> commit SHA)
        └── main -> <commitSha>
```

### Cache Management

You can inspect and manage the cache programmatically:

```php
// List all cached repositories
$repos = $hf->cache()->list();
foreach ($repos as $repo) {
    echo "{$repo['id']} ({$repo['type']}): " . round($repo['size'] / 1024 / 1024, 2) . " MB\n";
}

// Delete a specific repository (frees up space)
$hf->cache()->delete('google/bert-base-uncased');

// Clear the entire cache (use with caution!)
$hf->cache()->clear();
```

### Default Cache Location

The cache is stored in a platform-appropriate location:
- **Linux**: `~/.cache/huggingface/hub` or `$XDG_CACHE_HOME/huggingface/hub`
- **macOS**: `~/Library/Caches/huggingface/hub`
- **Windows**: `%LOCALAPPDATA%\huggingface\hub`

You can override with the `HF_HUB_CACHE` or `HF_HOME` environment variables.

### Custom Cache Directory

```php
$hf = HuggingFace::factory()
    ->withToken('token')
    ->withCacheDir('/custom/cache/path')
    ->make();
```

### Downloading to Cache

```php
// Download single file to cache
$cachedPath = $hub->repo('model')
    ->download('config.json')
    ->save();

// Check if file is cached
$isCached = $hub->repo('model')
    ->isCached('config.json');

// Get cached file path
$cachedPath = $hub->repo('model')
    ->getCachedPath('config.json');
```

### Disabling Cache

```php
$content = $hub->repo('model')
    ->download('file.bin')
    ->useCache(false)
    ->getContent();
```

### Force Re-download

```php
$content = $hub->repo('model')
    ->download('file.bin')
    ->force()
    ->getContent();
```

## Error Handling

The library uses specific exception types for different error conditions:

```php
use Codewithkyrian\HuggingFace\Exceptions\{
    HuggingFaceException,
    AuthenticationException,
    RateLimitException,
    NotFoundException,
    ApiException,
    NetworkException
};

try {
    $modelInfo = $hf->hub()->modelInfo('gp2');
} catch (AuthenticationException $e) {
    // Invalid or expired token
    echo "Auth error: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Too many requests
    echo "Rate limited. Retry after: " . $e->retryAfter . " seconds";
} catch (NotFoundException $e) {
    // Model or resource not found
    echo "Not found: " . $e->getMessage();
} catch (NetworkException $e) {
    // Connection issues
    echo "Network error: " . $e->getMessage();
} catch (ApiException $e) {
    // Other API errors
    echo "API error ({$e->statusCode}): " . $e->getMessage();
}
```

### Automatic Retries

The library automatically retries on transient failures:
- **Server errors (5xx)**: Retried with exponential backoff
- **Rate limits (429)**: Retried after the `Retry-After` delay
- **Network failures**: Retried up to 3 times

This happens transparently so you don't need to implement retry logic yourself.

## Examples

The `examples/` directory contains ready-to-run scripts:

| Directory | Description |
|-----------|-------------|
| `examples/hub/` | Hub operations (search, download, upload) |
| `examples/inference/` | Inference API examples for all tasks |

Run any example:

```bash
HF_TOKEN=your_token php examples/inference/chat_completion.php

php examples/hub/search.php
```

## API Reference

See the [API Reference](docs/API.md) for complete documentation of all classes and methods.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

MIT License. See [LICENSE](LICENSE) for details.
