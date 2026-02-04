<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Providers\Replicate;

use Codewithkyrian\HuggingFace\Inference\Exceptions\OutputValidationException;

/**
 * Replicate text-to-image provider.
 *
 * @see https://replicate.com/docs/reference/http#create-prediction
 */
class TextToImageProvider extends BaseReplicateProvider
{
    public function preparePayload(array $args, string $model): array
    {
        $input = [];

        if (isset($args['inputs'])) {
            $input['prompt'] = $args['inputs'];
        }

        // Flatten parameters into input
        if (isset($args['parameters']) && \is_array($args['parameters'])) {
            $input = array_merge($input, $args['parameters']);
        }

        $payload = ['input' => $input];

        // Add version for versioned models
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

        // Output can be a string URL or array of URLs
        if (\is_string($output)) {
            return $this->fetchOutput($output);
        }

        if (\is_array($output) && isset($output[0]) && \is_string($output[0])) {
            return $this->fetchOutput($output[0]);
        }

        throw new OutputValidationException(
            'Expected Replicate output to be URL string or array of URLs',
            $response
        );
    }
}
