<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Enums;

/**
 * Supported inference providers.
 *
 * Providers determine where and how model inference is executed:
 *
 * - `HfInference`: Hugging Face's own Inference API (default)
 * - `Auto`: Automatically select the best available provider for a model
 * - External providers (Cerebras, Together, Groq, etc.) for specialized access
 */
enum InferenceProvider: string
{
    /** Hugging Face's Inference API (default) */
    case HfInference = 'hf-inference';

    /** Automatically select the best available provider */
    case Auto = 'auto';

    // External providers - each supports specific tasks
    case BlackForestLabs = 'black-forest-labs';
    case Cerebras = 'cerebras';
    case Cohere = 'cohere';
    case FalAi = 'fal-ai';
    case FeatherlessAi = 'featherless-ai';
    case FireworksAi = 'fireworks-ai';
    case Groq = 'groq';
    case Hyperbolic = 'hyperbolic';
    case Nebius = 'nebius';
    case Novita = 'novita';
    case Nscale = 'nscale';
    case OpenAI = 'openai';
    case OVHcloud = 'ovhcloud';
    case Replicate = 'replicate';
    case Sambanova = 'sambanova';
    case Scaleway = 'scaleway';
    case Together = 'together';
    case Zai = 'zai-org';

    /**
     * Get the router base URL for this provider.
     *
     * When using HF tokens, requests are routed through router.huggingface.co.
     * When using provider API keys directly, requests go to the provider's API.
     */
    public function routerBaseUrl(): string
    {
        return match ($this) {
            self::Auto => 'https://router.huggingface.co/v1',
            self::HfInference => 'https://router.huggingface.co/hf-inference',
            self::BlackForestLabs => 'https://router.huggingface.co/black-forest-labs',
            self::Cerebras => 'https://router.huggingface.co/cerebras',
            self::Cohere => 'https://router.huggingface.co/cohere',
            self::FalAi => 'https://router.huggingface.co/fal-ai',
            self::FeatherlessAi => 'https://router.huggingface.co/featherless-ai',
            self::FireworksAi => 'https://router.huggingface.co/fireworks-ai',
            self::Groq => 'https://router.huggingface.co/groq',
            self::Hyperbolic => 'https://router.huggingface.co/hyperbolic',
            self::Nebius => 'https://router.huggingface.co/nebius',
            self::Novita => 'https://router.huggingface.co/novita',
            self::Nscale => 'https://router.huggingface.co/nscale',
            self::OpenAI => 'https://router.huggingface.co/openai',
            self::OVHcloud => 'https://router.huggingface.co/ovhcloud',
            self::Replicate => 'https://router.huggingface.co/replicate',
            self::Sambanova => 'https://router.huggingface.co/sambanova',
            self::Scaleway => 'https://router.huggingface.co/scaleway',
            self::Together => 'https://router.huggingface.co/together',
            self::Zai => 'https://router.huggingface.co/zai-org',
        };
    }

    /**
     * Get the direct API base URL for this provider (when using provider keys).
     */
    public function directBaseUrl(): ?string
    {
        return match ($this) {
            self::Auto, self::HfInference => null, // Always use router
            self::BlackForestLabs => 'https://api.us1.bfl.ai',
            self::Cerebras => 'https://api.cerebras.ai',
            self::Cohere => 'https://api.cohere.com',
            self::FalAi => 'https://fal.run',
            self::FeatherlessAi => 'https://api.featherless.ai',
            self::FireworksAi => 'https://api.fireworks.ai/inference',
            self::Groq => 'https://api.groq.com/openai',
            self::Hyperbolic => 'https://api.hyperbolic.xyz',
            self::Nebius => 'https://api.studio.nebius.ai',
            self::Novita => 'https://api.novita.ai',
            self::Nscale => 'https://inference.api.nscale.com',
            self::OpenAI => 'https://api.openai.com',
            self::OVHcloud => 'https://oai.endpoints.kepler.ai.cloud.ovh.net',
            self::Replicate => 'https://api.replicate.com',
            self::Sambanova => 'https://api.sambanova.ai',
            self::Scaleway => 'https://api.scaleway.ai',
            self::Together => 'https://api.together.xyz',
            self::Zai => 'https://api.z.ai',
        };
    }

    /**
     * Whether this provider requires client-side routing (direct API access only).
     *
     * Closed-source providers that don't support HF token routing.
     */
    public function clientSideRoutingOnly(): bool
    {
        return match ($this) {
            self::OpenAI => true,
            default => false,
        };
    }

    /**
     * Get the Hub organization namespace for this provider.
     */
    public function hubOrg(): string
    {
        return match ($this) {
            self::Cohere => 'CohereLabs',
            self::FalAi => 'fal',
            self::Hyperbolic => 'Hyperbolic',
            self::Sambanova => 'sambanovasystems',
            self::Together => 'togethercomputer',
            default => $this->value,
        };
    }
}
