<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Builders;

use Codewithkyrian\HuggingFace\Http\CurlConnector;
use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Hub\Enums\SpaceHardwareFlavor;
use Codewithkyrian\HuggingFace\Hub\Enums\SpaceSdk;
use Codewithkyrian\HuggingFace\Hub\Managers\RepoManager;
use Codewithkyrian\HuggingFace\Support\CacheManager;
use Codewithkyrian\HuggingFace\Support\RepoId;

/**
 * Fluent builder for creating repositories on the Hub.
 */
final class CreateRepoBuilder
{
    private bool $isPrivate = false;
    private ?string $organization = null;
    private ?string $license = null;
    private ?bool $canonical = null;
    private ?SpaceSdk $sdk = null;
    private ?string $sdkVersion = null;
    private ?SpaceHardwareFlavor $hardware = null;
    private readonly string $name;

    public function __construct(
        private readonly string $hubUrl,
        private readonly HttpConnector $http,
        private readonly CurlConnector $curl,
        private readonly CacheManager $cache,
        string $name,
        private readonly RepoType $type = RepoType::Model,
    ) {
        if (str_contains($name, '/')) {
            [$this->organization, $this->name] = explode('/', $name, 2);
        } else {
            $this->name = $name;
        }
    }

    /**
     * Make the repository private.
     */
    public function private(): self
    {
        $clone = clone $this;
        $clone->isPrivate = true;

        return $clone;
    }

    /**
     * Make the repository public (default).
     */
    public function public(): self
    {
        $clone = clone $this;
        $clone->isPrivate = false;

        return $clone;
    }

    /**
     * Create under an organization instead of personal account.
     */
    public function organization(string $organization): self
    {
        $clone = clone $this;
        $clone->organization = $organization;

        return $clone;
    }

    /**
     * Set the license.
     */
    public function license(string $license): self
    {
        $clone = clone $this;
        $clone->license = $license;

        return $clone;
    }

    /**
     * Make the repository canonical.
     */
    public function canonical(bool $canonical = true): self
    {
        $clone = clone $this;
        $clone->canonical = $canonical;

        return $clone;
    }

    /**
     * Set the SDK for Spaces.
     */
    public function sdk(SpaceSdk $sdk, ?string $version): self
    {
        $clone = clone $this;
        $clone->sdk = $sdk;
        $clone->sdkVersion = $version;

        return $clone;
    }

    /**
     * Set the hardware for Spaces.
     */
    public function hardware(SpaceHardwareFlavor $hardware): self
    {
        $clone = clone $this;
        $clone->hardware = $hardware;

        return $clone;
    }

    /**
     * Create the repository and return its info.
     */
    public function save(): RepoManager
    {
        if (null === $this->organization) {
            $response = $this->http->get($this->hubUrl.'/api/whoami-v2');
            $this->organization = $response->json()['name'];
        }

        $payload = [
            'name' => $this->name,
            'type' => $this->type->value,
            'private' => $this->isPrivate,
        ];

        if (null !== $this->organization) {
            $payload['organization'] = $this->organization;
        }

        if (null !== $this->license) {
            $payload['license'] = $this->license;
        }

        if (null !== $this->canonical) {
            $payload['canonical'] = $this->canonical;
        }

        if (RepoType::Space === $this->type && null !== $this->sdk) {
            $payload['sdk'] = $this->sdk->value;
        }

        if (RepoType::Space === $this->type && null !== $this->sdkVersion) {
            $payload['sdkVersion'] = $this->sdkVersion;
        }

        if (RepoType::Space === $this->type && null !== $this->hardware) {
            $payload['hardware'] = $this->hardware->value;
        }

        $response = $this->http->post(
            $this->hubUrl.'/api/repos/create',
            $payload
        );

        $data = $response->json();

        $repoId = new RepoId($this->organization, $this->name);

        return new RepoManager($this->hubUrl, $this->http, $this->curl, $this->cache, $repoId, $this->type);
    }
}
