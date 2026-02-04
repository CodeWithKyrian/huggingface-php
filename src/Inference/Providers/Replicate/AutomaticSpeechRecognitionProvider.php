<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Replicate;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Replicate automatic speech recognition provider.
 *
 * @see https://replicate.com/docs/reference/http#create-prediction
 */
class AutomaticSpeechRecognitionProvider extends BaseReplicateProvider
{
    public function preparePayload(array $args, string $model): array
    {
        $input = [];

        if (isset($args['inputs'])) {
            $base64 = $args['inputs'];
            $mimeType = $args['content_type'] ?? 'audio/wav';
            $input['audio'] = "data:{$mimeType};base64,{$base64}";
        } elseif (isset($args['audio_url'])) {
            $input['audio'] = $args['audio_url'];
        }

        if (isset($args['parameters']) && \is_array($args['parameters'])) {
            $input = array_merge($input, $args['parameters']);
        }

        $payload = ['input' => $input];

        $version = $this->extractVersion($model);
        if (null !== $version) {
            $payload['version'] = $version;
        }

        return $payload;
    }

    public function getResponse(mixed $response): mixed
    {
        if (!\is_array($response) || !isset($response['output'])) {
            throw new OutputValidationException(
                'Expected Replicate response with output',
                $response
            );
        }

        $output = $response['output'];

        if (\is_string($output)) {
            return ['text' => $output];
        }

        if (\is_array($output) && isset($output[0]) && \is_string($output[0])) {
            return ['text' => $output[0]];
        }

        if (\is_array($output)) {
            if (isset($output['transcription'])) {
                return ['text' => $output['transcription']];
            }
            if (isset($output['translation'])) {
                return ['text' => $output['translation']];
            }
            if (isset($output['txt_file']) && \is_string($output['txt_file'])) {
                return ['text' => $this->fetchOutput($output['txt_file'])];
            }
        }

        throw new OutputValidationException(
            'Expected Replicate ASR output with text, transcription, or translation',
            $response
        );
    }
}
