<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch;

use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\DevBranches;
use Keboola\StorageApiBranch\Factory\ClientOptions;
use LogicException;

class ClientWrapper
{
    public const BRANCH_DEFAULT = 'default';

    private ClientOptions $clientOptions;
    private ?Client $client;
    private ?BranchAwareClient $branchClient;

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
     * Returns branchClient if a branch was configured and basicClient otherwise.
     *
     * @return Client|BranchAwareClient
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
        return $this->clientOptions->getBranchId();
    }

    public function getBranchName(): ?string
    {
        if ($this->hasBranch()) {
            $branches = new DevBranches($this->getBasicClient());
            return $branches->getBranch((int) $this->getBranchId())['name'];
        } else {
            return null;
        }
    }

    public function hasBranch(): bool
    {
        return $this->clientOptions->getBranchId() !== null;
    }

    public function getClientOptionsReadOnly(): ClientOptions
    {
        return clone $this->clientOptions;
    }
}
