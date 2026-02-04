# API Reference

Complete reference for all public classes and methods in the Hugging Face PHP library.

---

## Table of Contents

- [Core Classes](#core-classes)
  - [HuggingFace](#huggingface)
  - [HuggingFaceFactory](#huggingfacefactory)
- [Hub API](#hub-api)
  - [HubClient](#hubclient)
  - [RepoManager](#repomanager)
  - [DownloadBuilder](#downloadbuilder)
  - [CommitBuilder](#commitbuilder)
  - [RepoListBuilder](#repolistbuilder)
  - [CollectionListBuilder](#collectionlistbuilder)
  - [CollectionManager](#collectionmanager)
  - [CreateRepoBuilder](#createrepobuilder)
  - [CreateCollectionBuilder](#createcollectionbuilder)
- [Inference API](#inference-api)
  - [InferenceClient](#inferenceclient)
  - [ChatCompletionBuilder](#chatcompletionbuilder)
  - [TextGenerationBuilder](#textgenerationbuilder)
  - [FeatureExtractionBuilder](#featureextractionbuilder)
  - [TextClassificationBuilder](#textclassificationbuilder)
  - [SummarizationBuilder](#summarizationbuilder)
  - [QuestionAnsweringBuilder](#questionansweringbuilder)
  - [TranslationBuilder](#translationbuilder)
  - [FillMaskBuilder](#fillmaskbuilder)
  - [ZeroShotClassificationBuilder](#zeroshotclassificationbuilder)
  - [SentenceSimilarityBuilder](#sentencesimilaritybuilder)
  - [TextToImageBuilder](#texttoimagebuilder)
  - [ImageClassificationBuilder](#imageclassificationbuilder)
  - [ImageToTextBuilder](#imagetotextbuilder)
  - [TextToSpeechBuilder](#texttospeechbuilder)
  - [AutomaticSpeechRecognitionBuilder](#automaticspeechrecognitionbuilder)
  - [ObjectDetectionBuilder](#objectdetectionbuilder)
  - [TokenClassificationBuilder](#tokenclassificationbuilder)
- [Data Transfer Objects](#data-transfer-objects)
- [Enums](#enums)
- [Exceptions](#exceptions)

---

## Core Classes

### HuggingFace

Main entry point for the library.

```php
use Codewithkyrian\HuggingFace\HuggingFace;
```

#### Static Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `client(?string $token = null)` | `HuggingFace` | Create client with default settings |
| `factory()` | `HuggingFaceFactory` | Get factory for custom configuration |

#### Instance Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `hub()` | `HubClient` | Access Hub API |
| `inference(InferenceProvider\|string $provider = HfInference)` | `InferenceClient` | Access Inference API with optional provider |
| `isAuthenticated()` | `bool` | Check if client has valid token |

---

### HuggingFaceFactory

Fluent builder for configuring the client.

```php
$hf = HuggingFace::factory()
    ->withToken('hf_token')
    ->withCacheDir('/path/to/cache')
    ->make();
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `withToken(?string $token)` | `self` | Set API token |
| `withCacheDir(?string $path)` | `self` | Set custom cache directory |
| `withHubUrl(string $url)` | `self` | Set custom Hub endpoint |
| `withHttpClient(ClientInterface $client)` | `self` | Use custom PSR-18 client |
| `make()` | `HuggingFace` | Build the client |

---

## Hub API

### HubClient

Manages Hub operations.

```php
$hub = $hf->hub();
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `repo(string $repoId, RepoType $type = Model)` | `RepoManager` | Get repository manager |
| `model(string $repoId)` | `RepoManager` | Get model repository |
| `dataset(string $repoId)` | `RepoManager` | Get dataset repository |
| `space(string $repoId)` | `RepoManager` | Get Space repository |
| `createRepo(string $name, RepoType $type = Model)` | `CreateRepoBuilder` | Create new repository |
| `models()` | `RepoListBuilder` | Search models |
| `datasets()` | `RepoListBuilder` | Search datasets |
| `spaces()` | `RepoListBuilder` | Search Spaces |
| `collections()` | `CollectionListBuilder` | Search collections |
| `collection(string $slug)` | `CollectionManager` | Get collection manager |
| `createCollection(string $title, ?string $namespace = null)` | `CreateCollectionBuilder` | Create collection |
| `modelInfo(string $repoId)` | `ModelInfo` | Get model metadata |
| `datasetInfo(string $repoId)` | `DatasetInfo` | Get dataset metadata |
| `spaceInfo(string $repoId)` | `SpaceInfo` | Get Space metadata |
| `whoami()` | `WhoAmIUser\|WhoAmIOrg\|WhoAmIApp` | Get authenticated user info |

---

### RepoManager

Manages operations on a repository at a specific revision.

```php
$repo = $hub->repo('bert-base-uncased');
$v1Repo = $repo->revision('v1.0');
```

#### Core Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `revision(string $revision)` | `RepoManager` | Get manager for different revision |
| `info(array $expand = [])` | `ModelInfo\|DatasetInfo\|SpaceInfo` | Get repository metadata |
| `exists()` | `bool` | Check if repository exists |
| `checkAccess()` | `void` | Verify read access (throws on failure) |

#### File Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `files(bool $recursive = false, bool $expand = false, ?string $path = null)` | `Generator<PathInfo>` | List files |
| `fileExists(string $path)` | `bool` | Check file existence |
| `fileInfo(string $path, bool $expand = true)` | `PathInfo\|null` | Get file metadata |
| `pathsInfo(array $paths, bool $expand = false)` | `PathInfo[]` | Get metadata for multiple files |
| `download(string $filename)` | `DownloadBuilder` | Download a file |
| `snapshot(?array $allowPatterns = null, ?array $ignorePatterns = null, bool $force = false)` | `string` | Download entire repository |
| `isCached(string $path)` | `bool` | Check if file is cached |
| `getCachedPath(string $path)` | `string\|null` | Get cached file path |

#### Commit Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `commit(string $message)` | `CommitBuilder` | Start multi-file commit |
| `uploadFile(string $path, mixed $content, ?string $message = null)` | `CommitOutput` | Upload single file |
| `uploadFiles(array $files, ?string $message = null)` | `CommitOutput` | Upload multiple files |
| `deleteFile(string $path, ?string $message = null)` | `CommitOutput` | Delete single file |
| `deleteFiles(array $paths, ?string $message = null)` | `CommitOutput` | Delete multiple files |
| `commits(int $batchSize = 100)` | `Generator<RepoCommit>` | List commits |
| `commitCount()` | `int` | Get total commit count |

#### Management Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `update(array $settings)` | `RepositoryInfo` | Update settings |
| `setVisibility(Visibility $visibility)` | `RepositoryInfo` | Change visibility |
| `delete(bool $missingOk = false)` | `void` | Delete repository |
| `move(string $newName)` | `RepoManager` | Rename repository |
| `fork(?string $targetNamespace = null)` | `RepoManager` | Fork repository |

#### Branch Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `createBranch(string $name, ?string $revision = null, bool $empty = false, bool $overwrite = false)` | `void` | Create branch |
| `deleteBranch(string $branch)` | `void` | Delete branch |
| `refs()` | `GitRefs` | List all refs |
| `branches()` | `GitRef[]` | List branches |
| `tags()` | `GitRef[]` | List tags |

---

### DownloadBuilder

Fluent builder for downloading files.

```php
$content = $repo->download('config.json')->getContent();
$config = $repo->download('config.json')->json();
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `useCache(bool $use = true)` | `self` | Enable/disable caching |
| `force()` | `self` | Re-download even if cached |
| `resumable(bool $resumable = true)` | `self` | Enable resumable downloads |
| `onProgress(callable $callback)` | `self` | Progress callback `(int $downloaded, int $total)` |
| `save(?string $directory = null)` | `string` | Download to directory (or cache if null), return path |
| `getContent()` | `string` | Get file content as string |
| `json()` | `array` | Download and parse as JSON |
| `info()` | `FileDownloadInfo` | Get metadata without downloading |
| `isCached()` | `bool` | Check if file is cached |

---

### CommitBuilder

Build multi-file commits.

```php
$repo->commit('Add model files')
    ->addFile('config.json', json_encode($config))
    ->addFile('model.bin', '/local/path/model.bin')
    ->deleteFile('old-file.txt')
    ->push();
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `addFile(string $path, mixed $content)` | `self` | Add file (string, path, URL, or resource) |
| `deleteFile(string $path)` | `self` | Mark file for deletion |
| `branch(string $branch)` | `self` | Target branch |
| `push()` | `CommitOutput` | Execute commit |

---

### RepoListBuilder

Search and list repositories.

```php
$models = $hub->models()
    ->search('bert')
    ->task('text-classification')
    ->limit(20)
    ->get();
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `search(string $query)` | `self` | Full-text search |
| `author(string $author)` | `self` | Filter by author |
| `task(string $task)` | `self` | Filter by pipeline task |
| `library(string $library)` | `self` | Filter by library |
| `language(string $language)` | `self` | Filter by language |
| `tag(string $tag)` | `self` | Add tag filter |
| `tags(array $tags)` | `self` | Filter by multiple tags |
| `filter(string $filter)` | `self` | Custom filter string |
| `sort(SortField $field)` | `self` | Sort results |
| `ascending()` | `self` | Sort ascending |
| `descending()` | `self` | Sort descending |
| `limit(?int $limit)` | `self` | Maximum results |
| `full(bool $full = true)` | `self` | Include full info |
| `withConfig(bool $config = true)` | `self` | Include config data |
| `get()` | `Generator<ModelInfo\|DatasetInfo\|SpaceInfo>` | Execute search |
| `first()` | `ModelInfo\|DatasetInfo\|SpaceInfo\|null` | Get first result |

---

### CollectionListBuilder

Search and list collections.

```php
$collections = $hub->collections()
    ->search('bert')
    ->owner('huggingface')
    ->limit(10)
    ->get();
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `search(string $query)` | `self` | Search titles and descriptions |
| `owner(string $owner)` | `self` | Filter by owner |
| `item(string $item)` | `self` | Filter by item (e.g., `models/bert-base`) |
| `sort(CollectionSortField $field)` | `self` | Sort results |
| `limit(?int $limit)` | `self` | Maximum results |
| `offset(int $offset)` | `self` | Skip results |
| `get()` | `Generator<CollectionInfo>` | Execute search |

---

### CollectionManager

Manage a specific collection.

```php
$collection = $hub->collection('my-collection-slug');
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `info()` | `CollectionInfo` | Get collection info |
| `addItem(string $itemId, CollectionItemType\|string $type, ?string $note = null)` | `void` | Add item |
| `deleteItem(string $itemId)` | `void` | Remove item (use object ID, not repo ID) |
| `delete()` | `void` | Delete collection |

---

### CreateRepoBuilder

Create new repositories.

```php
$repo = $hub->createRepo('my-model', RepoType::Model)
    ->private()
    ->license('mit')
    ->save();
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `private()` | `self` | Create as private |
| `public()` | `self` | Create as public (default) |
| `organization(string $org)` | `self` | Create under organization |
| `license(string $license)` | `self` | Set license (e.g., `mit`) |
| `canonical(bool $canonical = true)` | `self` | Make canonical |
| `sdk(SpaceSdk $sdk, ?string $version)` | `self` | Set Space SDK (Spaces only) |
| `hardware(SpaceHardwareFlavor $hw)` | `self` | Set hardware (Spaces only) |
| `save()` | `RepoManager` | Create repository |

---

### CreateCollectionBuilder

Create new collections.

```php
$collection = $hub->createCollection('My Models')
    ->description('Curated list')
    ->private()
    ->save();
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `description(string $description)` | `self` | Set description |
| `private()` | `self` | Make private |
| `public()` | `self` | Make public (default) |
| `save()` | `CollectionManager` | Create collection |

---

## Inference API

### InferenceClient

Access inference tasks on Hugging Face models. Get an inference client by calling `inference()` on the main `HuggingFace` instance.

```php
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;

// Default (Hugging Face Inference API)
$inference = $hf->inference();

// External provider (enum)
$inference = $hf->inference(InferenceProvider::Together);

// External provider (string slug)
$inference = $hf->inference('groq');

// Custom endpoint URL
$inference = $hf->inference('https://your-endpoint.huggingface.cloud');
```

#### Configuration Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `billTo(string $orgId)` | `InferenceClient` | Bill requests to organization |
| `getProvider()` | `InferenceProvider` | Get current provider |
| `getEndpointUrl()` | `?string` | Get custom endpoint URL if set |

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

#### Task Methods

Each task method accepts a model ID and returns a corresponding builder.

| Method | Returns | Description |
|--------|---------|-------------|
| `chatCompletion(string $model)` | `ChatCompletionBuilder` | Conversational AI |
| `textGeneration(string $model)` | `TextGenerationBuilder` | Text completion |
| `featureExtraction(string $model)` | `FeatureExtractionBuilder` | Embeddings |
| `textClassification(string $model)` | `TextClassificationBuilder` | Text classification |
| `tokenClassification(string $model)` | `TokenClassificationBuilder` | Token Classification (NER) |
| `summarization(string $model)` | `SummarizationBuilder` | Text summarization |
| `questionAnswering(string $model)` | `QuestionAnsweringBuilder` | Extractive QA |
| `translation(string $model)` | `TranslationBuilder` | Translation |
| `fillMask(string $model)` | `FillMaskBuilder` | Masked token prediction |
| `zeroShotClassification(string $model)` | `ZeroShotClassificationBuilder` | Zero-shot classification |
| `sentenceSimilarity(string $model)` | `SentenceSimilarityBuilder` | Sentence similarity |
| `objectDetection(string $model)` | `ObjectDetectionBuilder` | Object Detection |
| `textToImage(string $model)` | `TextToImageBuilder` | Image generation |
| `imageClassification(string $model)` | `ImageClassificationBuilder` | Image classification |
| `imageToText(string $model)` | `ImageToTextBuilder` | Image captioning |
| `textToSpeech(string $model)` | `TextToSpeechBuilder` | Text to audio |
| `automaticSpeechRecognition(string $model)` | `AutomaticSpeechRecognitionBuilder` | Audio transcription |

---

### ChatCompletionBuilder

Build conversational chat requests.

```php
$chat = $inference->chatCompletion('meta-llama/Llama-3.1-8B-Instruct');

$response = $chat
    ->system('You are helpful.')
    ->user('Hello!')
    ->generate();
```

#### Message Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `system(string $content)` | `self` | Add system message |
| `user(string $content)` | `self` | Add user message |
| `assistant(string $content)` | `self` | Add assistant message |
| `messages(array $messages)` | `self` | Set all messages |

#### Parameter Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `maxTokens(int $tokens)` | `self` | Maximum tokens to generate |
| `temperature(float $temp)` | `self` | Sampling temperature (0.0–2.0) |
| `topP(float $p)` | `self` | Nucleus sampling probability |
| `topK(int $k)` | `self` | Top-k sampling |
| `stop(array $sequences)` | `self` | Stop sequences |
| `seed(int $seed)` | `self` | Random seed |
| `frequencyPenalty(float $penalty)` | `self` | Reduce token repetition |
| `presencePenalty(float $penalty)` | `self` | Encourage new topics |
| `logprobs(bool $logprobs, ?int $topLogprobs)` | `self` | Return log probabilities |
| `responseFormat(array $format)` | `self` | Set response format (e.g. JSON) |
| `tool(ChatCompletionTool $tool)` | `self` | Add a tool |
| `tools(array $tools)` | `self` | Add multiple tools (array of ChatCompletionTool) |
| `toolChoice(string\|array $choice)` | `self` | Control tool choice |

#### Execution Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `generate()` | `ChatCompletionOutput` | Generate response |
| `stream()` | `Generator<ChatCompletionStreamOutput>` | Stream response chunks |

---

### TextGenerationBuilder

Generate text completions.

```php
$generator = $inference->textGeneration('gpt2');

$response = $generator
    ->maxNewTokens(100)
    ->temperature(0.7)
    ->execute('The future of AI is');
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `maxNewTokens(int $tokens)` | `self` | Maximum new tokens |
| `temperature(float $temp)` | `self` | Sampling temperature |
| `topP(float $p)` | `self` | Nucleus sampling |
| `topK(int $k)` | `self` | Top-k sampling |
| `doSample(bool $sample)` | `self` | Enable/disable sampling |
| `stop(array $sequences)` | `self` | Stop sequences |
| `repetitionPenalty(float $penalty)` | `self` | Penalize repetition |
| `returnFullText(bool $full)` | `self` | Include prompt in output |
| `seed(int $seed)` | `self` | Random seed |
| `truncate(int $len)` | `self` | Truncate inputs |
| `watermark(bool $b)` | `self` | Enable watermarking |
| `frequencyPenalty(float $p)` | `self` | Frequency penalty |
| `bestOf(int $n)` | `self` | Best of N |
| `decoderInputDetails(bool $b)` | `self` | Return decoder input details |
| `execute(string $prompt)` | `TextGenerationOutput` | Generate text |

---

### FeatureExtractionBuilder

Generate embeddings.

```php
$embedder = $inference->featureExtraction('sentence-transformers/all-MiniLM-L6-v2');

$embeddings = $embedder->normalize()->execute('Hello world');
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `normalize()` | `self` | Normalize embeddings to unit length |
| `truncate()` | `self` | Truncate to model's max length |
| `promptName(string $name)` | `self` | Use specific prompt template |
| `execute(string\|array $inputs)` | `array` | Generate embeddings |

---

### TextClassificationBuilder

Classify text into categories.

```php
$classifier = $inference->textClassification('distilbert-base-uncased-finetuned-sst-2-english');

$results = $classifier->execute('I love this!');
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `topK(int $k)` | `self` | Return top K labels |
| `functionToApply(ClassificationOutputTransform $f)` | `self` | Function to apply (sigmoid, none, etc) |
| `execute(string $inputs)` | `array<ClassificationOutput>` | Classify text |

---

### SummarizationBuilder

Summarize text.

```php
$summarizer = $inference->summarization('facebook/bart-large-cnn');

$result = $summarizer
    ->maxLength(130)
    ->minLength(30)
    ->execute($longText);
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `maxLength(int $length)` | `self` | Maximum summary length |
| `minLength(int $length)` | `self` | Minimum summary length |
| `doSample(bool $sample)` | `self` | Enable sampling |
| `temperature(float $temp)` | `self` | Sampling temperature |
| `cleanUpTokenizationSpaces(bool $b)` | `self` | Clean up spaces |
| `truncation(TruncationStrategy $strategy)` | `self` | Truncation strategy |
| `execute(string $inputs)` | `SummarizationOutput` | Summarize text |

---

### QuestionAnsweringBuilder

Extract answers from context.

```php
$qa = $inference->questionAnswering('deepset/roberta-base-squad2');

$result = $qa->execute('Who created PHP?', $context);
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `topK(int $k)` | `self` | Number of answers |
| `docStride(int $s)` | `self` | Document stride |
| `maxAnswerLen(int $l)` | `self` | Max answer length |
| `maxQuestionLen(int $l)` | `self` | Max question length |
| `maxSeqLen(int $l)` | `self` | Max sequence length |
| `alignToWords(bool $b)` | `self` | Align to words |
| `handleImpossibleAnswer(bool $b)` | `self` | Handle impossible answers |
| `execute(string $question, string $context)` | `QuestionAnsweringOutput` | Extract answer |

---

### TranslationBuilder

Translate text.

```php
$translator = $inference->translation('Helsinki-NLP/opus-mt-en-fr');

$result = $translator->execute('Hello world');
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `srcLang(string $lang)` | `self` | Source language |
| `tgtLang(string $lang)` | `self` | Target language |
| `cleanUpTokenizationSpaces(bool $b)` | `self` | Clean up spaces |
| `truncation(TruncationStrategy $s)` | `self` | Truncation strategy |
| `maxNewTokens(int $n)` | `self` | Max tokens |
| `execute(string $inputs)` | `TranslationOutput` | Translate text |

---

### FillMaskBuilder

Predict masked tokens.

```php
$mask = $inference->fillMask('bert-base-uncased');

$results = $mask->execute('Paris is the [MASK] of France.');
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `execute(string $inputs)` | `array<FillMaskOutput>` | Predict masked tokens |

---

### ZeroShotClassificationBuilder

Classify without training.

```php
$zeroShot = $inference->zeroShotClassification('facebook/bart-large-mnli');

$results = $zeroShot->execute('Book a flight', ['travel', 'finance']);
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `multiLabel(bool $enable)` | `self` | Allow multiple labels |
| `hypothesisTemplate(string $template)` | `self` | Custom schema template |
| `execute(string $inputs, array $labels)` | `array<ClassificationOutput>` | Classify |

---

### SentenceSimilarityBuilder

Compare sentence similarity.

```php
$similarity = $inference->sentenceSimilarity('sentence-transformers/all-MiniLM-L6-v2');

$scores = $similarity->execute('I love cats', ['I love dogs', 'Nice weather']);
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `execute(string $sourceSentence, array $sentences)` | `array<float>` | Get similarity scores |

---

### TextToImageBuilder

Generate images from text.

```php
$imageGen = $inference->textToImage('black-forest-labs/FLUX.1-schnell');

$imageData = $imageGen
    ->numInferenceSteps(20)
    ->execute('A sunset over mountains');
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `numInferenceSteps(int $steps)` | `self` | Denoising steps |
| `guidanceScale(float $scale)` | `self` | Prompt adherence |
| `width(int $px)` | `self` | Output width |
| `height(int $px)` | `self` | Output height |
| `size(int $width, int $height)` | `self` | Set both dimensions |
| `seed(int $seed)` | `self` | Random seed |
| `negativePrompt(string $prompt)` | `self` | What to avoid |
| `execute(string $prompt)` | `string` | Generate image (raw bytes) |
| `save(string $prompt, string $path)` | `string` | Generate and save to file |

---

### ImageClassificationBuilder

Classify images.

```php
$classifier = $inference->imageClassification('google/vit-base-patch16-224');

$results = $classifier->execute($imageUrl);
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `execute(string $inputs)` | `array<ClassificationOutput>` | Classify image (URL or base64) |

---

### ImageToTextBuilder

Generate captions for images.

```php
$captioner = $inference->imageToText('Salesforce/blip-image-captioning-base');

$result = $captioner->execute($imageUrl);
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `execute(string $inputs)` | `TextGenerationOutput` | Generate caption (URL or base64) |

---

### TextToSpeechBuilder

Convert text to audio.

```php
$tts = $inference->textToSpeech('espnet/kan-bayashi_ljspeech_vits');

$audioData = $tts->execute('Hello world');
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `maxNewTokens(int $n)` | `self` | Max tokens |
| `doSample(bool $b)` | `self` | Enable sampling |
| `temperature(float $t)` | `self` | Temperature |
| `execute(string $inputs)` | `string` | Generate audio (raw bytes) |
| `save(string $inputs, string $path)` | `string` | Generate and save to file |

---

### AutomaticSpeechRecognitionBuilder

Transcribe audio to text.

```php
$transcriber = $inference->automaticSpeechRecognition('openai/whisper-large-v3');

$result = $transcriber->execute('/path/to/audio.mp3');
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `returnTimestamps(bool\|string $val)` | `self` | Return timestamps |
| `chunkLengthS(float $num)` | `self` | Chunk length in seconds |
| `strideLengthS(float $num)` | `self` | Stride length in seconds |
| `language(string $lang)` | `self` | Language |
| `task(string $task)` | `self` | Task (transcribe/translate) |
| `execute(string $audioInput)` | `AutomaticSpeechRecognitionOutput` | Transcribe audio |

---

### TokenClassificationBuilder

Token classification (NER).

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `aggregationStrategy(TokenClassificationAggregationStrategy $strategy)` | `self` | Aggregation strategy |
| `ignoreLabels(array $labels)` | `self` | Ignore specific labels |
| `stride(int $stride)` | `self` | Stride |
| `execute(string $inputs)` | `array<TokenClassificationOutput>` | Classify tokens |

---

### ObjectDetectionBuilder

Detect objects in images.

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `threshold(float $threshold)` | `self` | Probability threshold |
| `execute(string $inputs)` | `array<ObjectDetectionOutput>` | Detect objects (URL/base64) |

---

## Data Transfer Objects

### Chat Completion

#### ChatCompletionOutput

```php
$response->id;            // string
$response->model;         // string
$response->created;       // int (timestamp)
$response->choices;       // ChatCompletionChoice[]
$response->usage;         // ChatCompletionUsage|null

$response->content();     // string - First choice content
$response->finishReason(); // string - "stop", "length", etc.
```

#### ChatCompletionUsage

```php
$usage->promptTokens;     // int
$usage->completionTokens; // int
$usage->totalTokens;      // int
```

#### ChatCompletionTool

```php
$tool = ChatCompletionTool::function('get_weather')
    ->description('Get current weather')
    ->parameters(['type' => 'object', ...]);
```

### Text Generation

#### TextGenerationOutput

```php
$output->generatedText;   // string
```

### Classification

#### ClassificationOutput

```php
$output->label;           // string
$output->score;           // float (0.0–1.0)
```

### Summarization

#### SummarizationOutput

```php
$output->summaryText;     // string
```

### Question Answering

#### QuestionAnsweringOutput

```php
$output->answer;          // string
$output->score;           // float
$output->start;           // int (position in context)
$output->end;             // int (position in context)
```

### Translation

#### TranslationOutput

```php
$output->translationText; // string
```

### Fill Mask

#### FillMaskOutput

```php
$output->score;           // float
$output->token;           // int
$output->tokenStr;        // string
$output->sequence;        // string (full text with token)
```

### Speech Recognition

#### AutomaticSpeechRecognitionOutput

```php
$output->text;            // string
```

### Repository Info

#### ModelInfo / DatasetInfo / SpaceInfo

```php
$info->id;                // string
$info->author;            // ?string
$info->private;           // bool
$info->gated;             // bool|string
$info->downloads;         // int
$info->likes;             // int
$info->tags;              // array
$info->createdAt;         // ?DateTimeImmutable
$info->updatedAt;         // ?DateTimeImmutable

$info->fullName();        // string
$info->url();             // string
```

#### PathInfo

```php
$file->path;              // string
$file->size;              // int
$file->blobId;            // ?string
$file->lfs;               // ?array

$file->filename();        // string
$file->directory();       // string
$file->extension();       // ?string
$file->isLfs();           // bool
```

---

## Enums

### RepoType

```php
use Codewithkyrian\HuggingFace\Enums\RepoType;

RepoType::Model;
RepoType::Dataset;
RepoType::Space;
```

### Visibility

```php
use Codewithkyrian\HuggingFace\Enums\Visibility;

Visibility::Public;
Visibility::Private;
```

### InferenceProvider

```php
use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;

InferenceProvider::HuggingFace;
InferenceProvider::Together;
InferenceProvider::Replicate;
// ... and more
```

### SortField

```php
use Codewithkyrian\HuggingFace\Enums\SortField;

SortField::Downloads;
SortField::Likes;
SortField::Created;
SortField::Modified;
```

### CollectionItemType

```php
use Codewithkyrian\HuggingFace\Enums\CollectionItemType;

CollectionItemType::Model;
CollectionItemType::Dataset;
CollectionItemType::Space;
CollectionItemType::Paper;
```

### SpaceSdk

```php
use Codewithkyrian\HuggingFace\Enums\SpaceSdk;

SpaceSdk::Gradio;
SpaceSdk::Streamlit;
SpaceSdk::Static;
SpaceSdk::Docker;
```
---

## Exceptions

All exceptions extend `HuggingFaceException`.

| Exception | Description |
|-----------|-------------|
| `AuthenticationException` | Invalid or missing token |
| `RateLimitException` | Too many requests |
| `NotFoundException` | Resource not found |
| `ApiException` | General API errors |
| `NetworkException` | Connection failures |
| `ValidationException` | Invalid input |
| `InputException` | Missing required input (Inference API) |

### RateLimitException

```php
try {
    $result = $inference->textClassification('model')->execute('text');
} catch (RateLimitException $e) {
    $seconds = $e->retryAfter;
    sleep($seconds);
    // Retry...
}
```

### ApiException

```php
try {
    $result = $hub->modelInfo('invalid-model');
} catch (ApiException $e) {
    echo $e->statusCode;   // HTTP status
    echo $e->getMessage(); // Error message
}
```
