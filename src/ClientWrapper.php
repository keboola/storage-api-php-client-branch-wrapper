<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch;

use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\DevBranches;
use Keboola\StorageApiBranch\Factory\ClientOptions;
use LogicException;

class ClientWrapper
{
    public const BRANCH_DEFAULT = 'default';

    private ClientOptions $clientOptions;
    private ?Client $client;
    private ?BranchAwareClient $branchClient;
    private ?bool $isDefaultBranch = null;
    private string $branchId;
    private string $branchName;

    public function __construct(ClientOptions $clientOptions)
    {
        $this->clientOptions = $clientOptions;
    }

    public function getBasicClient(): Client
    {
        if (empty($this->client)) {
            $this->client = new Client($this->clientOptions->getClientConstructOptions());
            $this->client->setRunId($this->clientOptions->getRunId());
            $this->client->setBackendConfiguration($this->clientOptions->getBackendConfiguration());
        }
        return $this->client;
    }

    public function getBranchClient(): BranchAwareClient
    {
        if (!$this->hasBranch()) {
            throw new LogicException('Branch is not set.');
        }
        if (empty($this->branchClient)) {
            $this->branchClient = new BranchAwareClient(
                (string) $this->clientOptions->getBranchId(),
                $this->clientOptions->getClientConstructOptions(),
            );
            $this->branchClient->setRunId($this->clientOptions->getRunId());
            $this->branchClient->setBackendConfiguration($this->clientOptions->getBackendConfiguration());
        }
        return $this->branchClient;
    }

    /**
     * Returns branchClient if useBranchStorage flag is configured
     *
     * @return Client|BranchAwareClient
     */
    public function getTableAndFileStorageClient(): Client
    {
        if ($this->clientOptions->useBranchStorage()) {
            return $this->getBranchClient();
        }
        return $this->getBasicClient();
    }

    /**
     * Returns branchClient if a branch was configured and basicClient otherwise.
     *
     * @return Client|BranchAwareClient
     * @deprecated Use getBranchClient for all endpoints that support branches.
     */
    public function getBranchClientIfAvailable(): Client
    {
        if ($this->hasBranch()) {
            return $this->getBranchClient();
        }

        return $this->getBasicClient();
    }

    public function getBranchId(): ?string
    {
        $this->resolveBranchId();
        return $this->branchId;
        //return $this->clientOptions->getBranchId();
    }

    public function getBranchName(): ?string
    {
        $this->resolveBranchId();
        return $this->branchName;

        /*
        if ($this->hasBranch()) {
            $branches = new DevBranches($this->getBasicClient());
            return $branches->getBranch((int) $this->getBranchId())['name'];
        } else {
            return null;
        }
        */
    }

    public function hasBranch(): bool
    {
        $this->resolveBranchId();
        return !$this->isDefaultBranch;
        //return $this->clientOptions->getBranchId() !== null;
    }

    public function getClientOptionsReadOnly(): ClientOptions
    {
        return clone $this->clientOptions;
    }

    private function resolveBranchId(): void
    {
        if ($this->isDefaultBranch !== null) {
            return;
        }
        $branchesApiClient = new DevBranches($this->getBasicClient());
        $branchId = $this->clientOptions->getBranchId();
        if ($branchId === null || $branchId === 'default') {
            foreach ($branchesApiClient->listBranches() as $branch) {
                if ($branch['isDefault']) {
                    $this->branchId = (string) $branch['id'];
                    $this->isDefaultBranch = true;
                    $this->branchName = (string) $branch['name'];
                    return;
                }
            }
        }

        foreach ($branchesApiClient->listBranches() as $branch) {
            if ($branchId === (string) $branch['id']) {
                $this->isDefaultBranch = $branch['isDefault'];
                $this->branchName = (string) $branch['name'];
                $this->branchId = (string) $branch['id'];
                return;
            }
        }
        throw new ClientException(sprintf('Can\'t resolve branchId: "%s".', $branchId));
    }
}
