<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Builders;

use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\Managers\CollectionManager;

/**
 * Fluent builder for creating collections on the Hub.
 */
final class CreateCollectionBuilder
{
    private ?string $description = null;
    private bool $private = false;

    public function __construct(
        private readonly string $hubUrl,
        private readonly HttpConnector $http,
        private readonly string $title,
        private readonly ?string $namespace = null, // Optional namespace (user or org)
    ) {}

    /**
     * Set the description of the collection.
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Make the collection private.
     */
    public function private(): self
    {
        $this->private = true;

        return $this;
    }

    /**
     * Make the collection public (default).
     */
    public function public(): self
    {
        $this->private = false;

        return $this;
    }

    /**
     * Create the collection.
     */
    public function save(): CollectionManager
    {
        $url = "{$this->hubUrl}/api/collections";

        $namespace = $this->namespace;
        if (null === $namespace) {
            $response = $this->http->get($this->hubUrl.'/api/whoami-v2');
            $namespace = $response->json()['name'];
        }

        $payload = [
            'title' => $this->title,
            'private' => $this->private,
            'namespace' => $namespace,
        ];

        if ($this->description) {
            $payload['description'] = $this->description;
        }

        $response = $this->http->post($url, $payload);
        $data = $response->json();

        $slug = $data['slug'];

        return new CollectionManager($this->hubUrl, $this->http, $slug);
    }
}
