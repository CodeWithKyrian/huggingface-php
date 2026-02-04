<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Inference\Exceptions\ProviderApiException;
use Codewithkyrian\HuggingFace\Inference\Providers\ProviderHelperInterface;

/**
 * Fluent builder for text-to-image generation requests.
 */
final class TextToImageBuilder
{
    private ?int $numInferenceSteps = null;
    private ?float $guidanceScale = null;
    private ?int $width = null;
    private ?int $height = null;
    private ?int $seed = null;
    private ?string $negativePrompt = null;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly HttpConnector $http,
        private readonly ProviderHelperInterface $helper,
        private readonly string $model,
        private readonly string $url,
        private readonly array $headers,
    ) {}

    /**
     * Set the number of inference steps.
     *
     * Higher values generally produce better quality but take longer.
     */
    public function numInferenceSteps(int $steps): self
    {
        $this->numInferenceSteps = $steps;

        return $this;
    }

    /**
     * Set the guidance scale (CFG scale).
     *
     * Higher values follow the prompt more closely.
     */
    public function guidanceScale(float $scale): self
    {
        $this->guidanceScale = $scale;

        return $this;
    }

    /**
     * Set the output image width.
     */
    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set the output image height.
     */
    public function height(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Set both width and height.
     */
    public function size(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * Set random seed for reproducibility.
     */
    public function seed(int $seed): self
    {
        $this->seed = $seed;

        return $this;
    }

    /**
     * Set negative prompt (what to avoid in the image).
     */
    public function negativePrompt(string $prompt): self
    {
        $this->negativePrompt = $prompt;

        return $this;
    }

    /**
     * Execute the request and return image data.
     *
     * @param string $prompt Image description
     *
     * @return string Raw image data (typically PNG format)
     */
    public function execute(string $prompt): string
    {
        $payload = $this->buildPayload($prompt);
        $body = $this->helper->preparePayload($payload, $this->model);

        $response = $this->http->post($this->url, $body, $this->headers);

        if (!$response->successful()) {
            throw ProviderApiException::fromResponse($response, $this->url);
        }

        // Returns binary image data
        return $response->body();
    }

    /**
     * Execute and save to a file.
     *
     * @param string $prompt Image description
     * @param string $path   Path to save the image file
     *
     * @return string The path where the file was saved
     */
    public function save(string $prompt, string $path): string
    {
        $imageData = $this->execute($prompt);
        file_put_contents($path, $imageData);

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $prompt): array
    {
        $payload = ['inputs' => $prompt];
        $parameters = [];

        if (null !== $this->numInferenceSteps) {
            $parameters['num_inference_steps'] = $this->numInferenceSteps;
        }

        if (null !== $this->guidanceScale) {
            $parameters['guidance_scale'] = $this->guidanceScale;
        }

        if (null !== $this->width) {
            $parameters['width'] = $this->width;
        }

        if (null !== $this->height) {
            $parameters['height'] = $this->height;
        }

        if (null !== $this->seed) {
            $parameters['seed'] = $this->seed;
        }

        if (null !== $this->negativePrompt) {
            $parameters['negative_prompt'] = $this->negativePrompt;
        }

        if (!empty($parameters)) {
            $payload['parameters'] = $parameters;
        }

        return $payload;
    }
}
