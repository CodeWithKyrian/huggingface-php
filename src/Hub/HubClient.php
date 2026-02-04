<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub;

use Codewithkyrian\HuggingFace\Exceptions\AuthenticationException;
use Codewithkyrian\HuggingFace\Http\CurlConnector;
use Codewithkyrian\HuggingFace\Http\HttpConnector;
use Codewithkyrian\HuggingFace\Hub\Builders\CollectionListBuilder;
use Codewithkyrian\HuggingFace\Hub\Builders\CreateCollectionBuilder;
use Codewithkyrian\HuggingFace\Hub\Builders\CreateRepoBuilder;
use Codewithkyrian\HuggingFace\Hub\Builders\DownloadBuilder;
use Codewithkyrian\HuggingFace\Hub\Builders\RepoListBuilder;
use Codewithkyrian\HuggingFace\Hub\DTOs\CollectionInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\DatasetInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\ModelInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\SpaceInfo;
use Codewithkyrian\HuggingFace\Hub\DTOs\WhoAmIApp;
use Codewithkyrian\HuggingFace\Hub\DTOs\WhoAmIOrg;
use Codewithkyrian\HuggingFace\Hub\DTOs\WhoAmIUser;
use Codewithkyrian\HuggingFace\Hub\Enums\RepoType;
use Codewithkyrian\HuggingFace\Hub\Managers\CollectionManager;
use Codewithkyrian\HuggingFace\Hub\Managers\RepoManager;
use Codewithkyrian\HuggingFace\Support\CacheManager;
use Codewithkyrian\HuggingFace\Support\RepoId;

/**
 * Client for interacting with the Hugging Face Hub API.
 *
 * Provides access to repository management, file operations,
 * search, and model/dataset cards.
 */
final class HubClient
{
    public function __construct(
        public readonly string $hubUrl,
        public readonly HttpConnector $http,
        public readonly CurlConnector $curl,
        private readonly CacheManager $cache,
    ) {}

    /**
     * Get the authenticated user's information.
     *
     * @throws AuthenticationException
     */
    public function whoami(): WhoAmIApp|WhoAmIOrg|WhoAmIUser
    {
        $response = $this->http->get($this->hubUrl.'/api/whoami-v2');
        $data = $response->json();

        return match ($data['type'] ?? null) {
            'user' => WhoAmIUser::fromArray($data),
            'org' => WhoAmIOrg::fromArray($data),
            'app' => WhoAmIApp::fromArray($data),
            default => throw new \InvalidArgumentException('Invalid whoami type: '.$data['type']),
        };
    }

    /**
     * Get a repository manager for a specific repository.
     */
    public function repo(string $repoId, RepoType $type = RepoType::Model): RepoManager
    {
        return new RepoManager($this->hubUrl, $this->http, $this->curl, $this->cache, RepoId::parse($repoId), $type);
    }

    /**
     * Get a repository manager for a dataset.
     */
    public function dataset(string $repoId): RepoManager
    {
        return $this->repo($repoId, RepoType::Dataset);
    }

    /**
     * Get a repository manager for a Space.
     */
    public function space(string $repoId): RepoManager
    {
        return $this->repo($repoId, RepoType::Space);
    }

    /**
     * Get a manager for a specific collection.
     */
    public function collection(string $slug): CollectionManager
    {
        return new CollectionManager($this->hubUrl, $this->http, $slug);
    }

    /**
     * List and filter models on the Hub.
     *
     * @return RepoListBuilder<ModelInfo>
     */
    public function models(): RepoListBuilder
    {
        /** @var RepoListBuilder<ModelInfo> */
        return new RepoListBuilder($this->hubUrl, $this->http, RepoType::Model);
    }

    /**
     * List and filter datasets on the Hub.
     *
     * @return RepoListBuilder<DatasetInfo>
     */
    public function datasets(): RepoListBuilder
    {
        /** @var RepoListBuilder<DatasetInfo> */
        return new RepoListBuilder($this->hubUrl, $this->http, RepoType::Dataset);
    }

    /**
     * List and filter Spaces on the Hub.
     *
     * @return RepoListBuilder<SpaceInfo>
     */
    public function spaces(): RepoListBuilder
    {
        /** @var RepoListBuilder<SpaceInfo> */
        return new RepoListBuilder($this->hubUrl, $this->http, RepoType::Space);
    }

    /**
     * List collections on the Hub.
     */
    public function collections(): CollectionListBuilder
    {
        return new CollectionListBuilder($this->hubUrl, $this->http);
    }

    /**
     * Get detailed model information.
     *
     * Convenience method for $hf->hub()->repo($id)->modelInfo()
     */
    public function modelInfo(string $repoId): ModelInfo
    {
        return $this->repo($repoId, RepoType::Model)->info();
    }

    /**
     * Get detailed dataset information.
     *
     * Convenience method for $hf->hub()->dataset($id)->datasetInfo()
     */
    public function datasetInfo(string $repoId): DatasetInfo
    {
        return $this->repo($repoId, RepoType::Dataset)->info();
    }

    /**
     * Get detailed space information.
     *
     * Convenience method for $hf->hub()->space($id)->spaceInfo()
     */
    public function spaceInfo(string $repoId): SpaceInfo
    {
        return $this->repo($repoId, RepoType::Space)->info();
    }

    /**
     * Get detailed collection information.
     *
     * Convenience method for $hf->hub()->collection($slug)->info()
     */
    public function collectionInfo(string $slug): CollectionInfo
    {
        return $this->collection($slug)->info();
    }

    /**
     * Create a new repository.
     */
    public function createRepo(string $name, RepoType $type = RepoType::Model): CreateRepoBuilder
    {
        return new CreateRepoBuilder($this->hubUrl, $this->http, $this->curl, $this->cache, $name, $type);
    }

    /**
     * Create a new collection.
     */
    public function createCollection(string $title, ?string $namespace = null): CreateCollectionBuilder
    {
        return new CreateCollectionBuilder($this->hubUrl, $this->http, $title, $namespace);
    }

    /**
     * Download a file from the Hub.
     */
    public function download(string $repoId, string $filename, RepoType $type = RepoType::Model): DownloadBuilder
    {
        return new DownloadBuilder(
            $this->hubUrl,
            $this->http,
            $this->curl,
            $this->cache,
            RepoId::parse($repoId),
            $filename,
            $type
        );
    }
}
