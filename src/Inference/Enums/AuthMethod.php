<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Enums;

/**
 * Authentication method for inference requests.
 *
 * Determines how tokens are used and how requests are routed:
 * - HfToken: Uses HF router to proxy requests (works with all providers)
 * - ProviderKey: Uses provider's direct API (requires provider-specific key)
 * - None: No authentication (for public endpoints)
 */
enum AuthMethod: string
{
    /**
     * Using a Hugging Face token (starts with "hf_").
     * Requests are routed through HF's router infrastructure.
     */
    case HfToken = 'hf-token';

    /**
     * Using a provider-specific API key.
     * Requests go directly to the provider's API.
     */
    case ProviderKey = 'provider-key';

    /**
     * No authentication.
     */
    case None = 'none';

    /**
     * Detect the auth method from a token string.
     */
    public static function fromToken(?string $token): self
    {
        if (null === $token || '' === $token) {
            return self::None;
        }

        if (str_starts_with($token, 'hf_')) {
            return self::HfToken;
        }

        return self::ProviderKey;
    }

    /**
     * Check if this method can be used with client-side-routing-only providers.
     */
    public function canUseWithClientSideRouting(): bool
    {
        return self::ProviderKey === $this;
    }
}
