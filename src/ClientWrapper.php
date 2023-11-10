<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch;

use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\DevBranches;
use Keboola\StorageApiBranch\Factory\ClientOptions;

class ClientWrapper
{
    public const BRANCH_DEFAULT = 'default';

    private ClientOptions $clientOptions;
    private ?Client $basicClient;
    private ?StorageApiToken $storageToken;
    private string $branchId;
    private string $defaultBranchId;
    /** @var Branch[] */
    private array $branches;

    public function __construct(ClientOptions $clientOptions)
    {
        $this->clientOptions = $clientOptions;
    }

    public function getBasicClient(): Client
    {
        if (empty($this->basicClient)) {
            $this->basicClient = new Client($this->clientOptions->getClientConstructOptions());
            $this->basicClient->setRunId($this->clientOptions->getRunId());
            $this->basicClient->setBackendConfiguration($this->clientOptions->getBackendConfiguration());
        }
        return $this->basicClient;
    }

    public function getClientForBranch(string $branchId): BranchAwareClient
    {
        if (!ctype_digit($branchId)) {
            throw new ClientException(sprintf('Branch ID must be a number. "%s" given.', $branchId));
        }
        $this->resolveBranches();
        if (!isset($this->branches[$branchId])) {
            throw new ClientException(sprintf('Branch with ID "%s" does not exist.', $branchId));
        }
        if (empty($this->branches[$branchId]->client)) {
            $branchClient = new BranchAwareClient(
                (int) $branchId,
                $this->clientOptions->getClientConstructOptions(),
            );
            $branchClient->setRunId($this->clientOptions->getRunId());
            $branchClient->setBackendConfiguration($this->clientOptions->getBackendConfiguration());
            $this->branches[$branchId]->client = $branchClient;
        }
        return $this->branches[$branchId]->client;
    }

    public function getBranchClient(): BranchAwareClient
    {
        return $this->getClientForBranch($this->getBranchId());
    }

    public function getClientForDefaultBranch(): BranchAwareClient
    {
        return $this->getClientForBranch($this->getDefaultBranch()->id);
    }

    /**
     * Returns branchClient if useBranchStorage flag is configured
     */
    public function getTableAndFileStorageClient(): Client|BranchAwareClient
    {
        if ($this->clientOptions->useBranchStorage()) {
            return $this->getBranchClient();
        }
        return $this->getBasicClient();
    }

    public function getBranchId(): string
    {
        $this->resolveBranches();
        return $this->branchId;
    }

    public function getBranchName(): string
    {
        $this->resolveBranches();
        return $this->branches[$this->branchId]->name;
    }

    /**
     * Returns true if the configured branch is NON-default. Returns false for the
     *  default/main/production branch.
     */
    public function isDevelopmentBranch(): bool
    {
        $this->resolveBranches();
        return !$this->branches[$this->branchId]->isDefault;
    }

    public function isDefaultBranch(): bool
    {
        $this->resolveBranches();
        return $this->branches[$this->branchId]->isDefault;
    }

    public function getDefaultBranch(): Branch
    {
        $this->resolveBranches();
        return $this->branches[$this->defaultBranchId];
    }

    public function getBranch(): Branch
    {
        $this->resolveBranches();
        return $this->branches[$this->branchId];
    }

    public function getClientOptionsReadOnly(): ClientOptions
    {
        return clone $this->clientOptions;
    }

    public function getToken(): StorageApiToken
    {
        if (empty($this->storageToken)) {
            $this->storageToken = new StorageApiToken(
                $this->getBranchClient()->verifyToken(),
                $this->getBranchClient()->getTokenString(),
            );
        }
        return $this->storageToken;
    }

    private function resolveBranches(): void
    {
        if (!empty($this->branches)) {
            return;
        }

        $branchesApi = new DevBranches($this->getBasicClient());
        $branchId = $this->clientOptions->getBranchId();

        foreach ($branchesApi->listBranches() as $branch) {
            $this->branches[(string) $branch['id']] = new Branch(
                (string) $branch['id'],
                (string) $branch['name'],
                (bool) $branch['isDefault'],
            );
            if ($branch['isDefault']) {
                $this->defaultBranchId = (string) $branch['id'];
            }
        }

        if (empty($this->defaultBranchId)) {
            throw new ClientException(sprintf('Can not find default branch for branchId: "%s".', $branchId));
        }

        if ($branchId === null || $branchId === 'default') {
            $this->branchId = $this->defaultBranchId;
        } else {
            $this->branchId = $branchId;
        }

        if (!isset($this->branches[$this->branchId])) {
            throw new ClientException(sprintf('Can not resolve branchId: "%s".', $branchId));
        }
    }
}
