<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Enums;

/**
 * Inference API task types.
 *
 * Tasks define the type of ML operation to perform. Each task has specific
 * input/output formats and is supported by different models and providers.
 *
 * This enum extends beyond the Hub Task enum to include all tasks supported
 * by the Inference API.
 */
enum InferenceTask: string
{
    // ========================================================================
    // NLP Tasks
    // ========================================================================

    /** Generate text continuations from a prompt */
    case TextGeneration = 'text-generation';

    /** Chat-style conversation with messages */
    case Conversational = 'conversational';

    /** Classify text into predefined categories */
    case TextClassification = 'text-classification';

    /** Identify and classify named entities in text */
    case TokenClassification = 'token-classification';

    /** Answer questions based on context */
    case QuestionAnswering = 'question-answering';

    /** Fill in masked tokens in text */
    case FillMask = 'fill-mask';

    /** Generate concise summaries of text */
    case Summarization = 'summarization';

    /** Translate text between languages */
    case Translation = 'translation';

    /** Answer questions about tables */
    case TableQuestionAnswering = 'table-question-answering';

    /** Calculate similarity between sentences */
    case SentenceSimilarity = 'sentence-similarity';

    /** Extract embedding vectors from text */
    case FeatureExtraction = 'feature-extraction';

    /** Classify text into categories without task-specific training */
    case ZeroShotClassification = 'zero-shot-classification';

    // ========================================================================
    // Vision Tasks
    // ========================================================================

    /** Classify images into categories */
    case ImageClassification = 'image-classification';

    /** Detect objects in images */
    case ObjectDetection = 'object-detection';

    /** Segment regions in images */
    case ImageSegmentation = 'image-segmentation';

    /** Generate text descriptions of images */
    case ImageToText = 'image-to-text';

    /** Generate images from text descriptions */
    case TextToImage = 'text-to-image';

    /** Transform images based on prompts */
    case ImageToImage = 'image-to-image';

    /** Generate videos from text descriptions */
    case TextToVideo = 'text-to-video';

    /** Generate videos from images */
    case ImageToVideo = 'image-to-video';

    /** Classify images into categories without task-specific training */
    case ZeroShotImageClassification = 'zero-shot-image-classification';

    // ========================================================================
    // Audio Tasks
    // ========================================================================

    /** Transcribe speech to text */
    case AutomaticSpeechRecognition = 'automatic-speech-recognition';

    /** Classify audio into categories */
    case AudioClassification = 'audio-classification';

    /** Generate speech from text */
    case TextToSpeech = 'text-to-speech';

    /** Generate audio from text (music, effects) */
    case TextToAudio = 'text-to-audio';

    /** Transform audio (enhancement, separation) */
    case AudioToAudio = 'audio-to-audio';

    // ========================================================================
    // Multimodal Tasks
    // ========================================================================

    /** Answer questions about documents */
    case DocumentQuestionAnswering = 'document-question-answering';

    /** Answer questions about images */
    case VisualQuestionAnswering = 'visual-question-answering';

    /** Transform images based on text and image input */
    case ImageTextToImage = 'image-text-to-image';

    /** Generate videos from text and image input */
    case ImageTextToVideo = 'image-text-to-video';

    // ========================================================================
    // Tabular Tasks
    // ========================================================================

    /** Classify tabular data */
    case TabularClassification = 'tabular-classification';

    /** Predict continuous values from tabular data */
    case TabularRegression = 'tabular-regression';

    /**
     * Get equivalent task types for routing.
     *
     * Some tasks share the same pipeline (e.g., feature-extraction and sentence-similarity).
     *
     * @return array<InferenceTask>
     */
    public function equivalentTasks(): array
    {
        return match ($this) {
            self::FeatureExtraction,
            self::SentenceSimilarity => [self::FeatureExtraction, self::SentenceSimilarity],
            default => [$this],
        };
    }

    /**
     * Check if this task supports streaming responses.
     */
    public function supportsStreaming(): bool
    {
        return match ($this) {
            self::TextGeneration,
            self::Conversational => true,
            default => false,
        };
    }

    /**
     * Get human-readable display name.
     */
    public function label(): string
    {
        return match ($this) {
            self::TextGeneration => 'Text Generation',
            self::Conversational => 'Chat Completion',
            self::TextClassification => 'Text Classification',
            self::TokenClassification => 'Token Classification',
            self::QuestionAnswering => 'Question Answering',
            self::FillMask => 'Fill Mask',
            self::Summarization => 'Summarization',
            self::Translation => 'Translation',
            self::TableQuestionAnswering => 'Table Question Answering',
            self::SentenceSimilarity => 'Sentence Similarity',
            self::FeatureExtraction => 'Feature Extraction',
            self::ZeroShotClassification => 'Zero-Shot Classification',
            self::ImageClassification => 'Image Classification',
            self::ObjectDetection => 'Object Detection',
            self::ImageSegmentation => 'Image Segmentation',
            self::ImageToText => 'Image to Text',
            self::TextToImage => 'Text to Image',
            self::ImageToImage => 'Image to Image',
            self::TextToVideo => 'Text to Video',
            self::ImageToVideo => 'Image to Video',
            self::ZeroShotImageClassification => 'Zero-Shot Image Classification',
            self::AutomaticSpeechRecognition => 'Speech Recognition',
            self::AudioClassification => 'Audio Classification',
            self::TextToSpeech => 'Text to Speech',
            self::TextToAudio => 'Text to Audio',
            self::AudioToAudio => 'Audio to Audio',
            self::DocumentQuestionAnswering => 'Document Question Answering',
            self::VisualQuestionAnswering => 'Visual Question Answering',
            self::ImageTextToImage => 'Image+Text to Image',
            self::ImageTextToVideo => 'Image+Text to Video',
            self::TabularClassification => 'Tabular Classification',
            self::TabularRegression => 'Tabular Regression',
        };
    }
}
