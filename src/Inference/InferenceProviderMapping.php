<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference;

use Codewithkyrian\HuggingFace\Inference\Enums\InferenceProvider;

/**
 * Represents an inference provider mapping for a model.
 *
 * This is returned from the Hub API when querying a model's inference providers.
 */
final class InferenceProviderMapping
{
    public function __construct(
        public readonly InferenceProvider $provider,
        public readonly string $hfModelId,
        public readonly string $providerId,
        public readonly string $status, // 'live' | 'staging'
        public readonly string $task,
    ) {}

    /**
     * Create from Hub API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $providerSlug = $data['provider'] ?? 'hf-inference';
        $provider = InferenceProvider::tryFrom($providerSlug) ?? InferenceProvider::HfInference;

        return new self(
            provider: $provider,
            hfModelId: $data['hfModelId'] ?? $data['hf_model_id'] ?? '',
            providerId: $data['providerId'] ?? $data['provider_id'] ?? '',
            status: $data['status'] ?? 'live',
            task: $data['task'] ?? '',
        );
    }

    /**
     * Check if this mapping is for a live (production-ready) provider.
     */
    public function isLive(): bool
    {
        return 'live' === $this->status;
    }
}
