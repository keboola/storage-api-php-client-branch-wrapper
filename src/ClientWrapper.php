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
    private ?Client $client;
    private ?BranchAwareClient $branchClient;
    private ?bool $isDefaultBranch = null;
    private string $branchId;
    private string $branchName;
    private string $defaultBranchId;
    private string $defaultBranchName;

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
        if (empty($this->branchClient)) {
            $this->branchClient = new BranchAwareClient(
                $this->getBranchId(),
                $this->clientOptions->getClientConstructOptions(),
            );
            $this->branchClient->setRunId($this->clientOptions->getRunId());
            $this->branchClient->setBackendConfiguration($this->clientOptions->getBackendConfiguration());
        }
        return $this->branchClient;
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
        $this->resolveBranchId();
        return $this->branchId;
    }

    public function getBranchName(): string
    {
        $this->resolveBranchId();
        return $this->branchName;
    }

    /**
     * Returns true if the configured branch is NON-default. Returns false for the
     *  default/main/production branch.
     */
    public function isDevelopmentBranch(): bool
    {
        $this->resolveBranchId();
        return !$this->isDefaultBranch;
    }

    public function isDefaultBranch(): bool
    {
        $this->resolveBranchId();
        // after branch is resolved, isDefaultBranch is always set
        return (bool) $this->isDefaultBranch;
    }

    public function getDefaultBranch(): array
    {
        $this->resolveBranchId();

        return [
            'branchId' => $this->defaultBranchId,
            'branchName' => $this->defaultBranchName,
            'isDefault' => true,
        ];
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

        foreach ($branchesApiClient->listBranches() as $branch) {
            if ($branch['isDefault']) {
                $this->defaultBranchId = (string) $branch['id'];
                $this->defaultBranchName = (string) $branch['name'];

                if ($branchId === null || $branchId === 'default') {
                    $this->isDefaultBranch = true;
                    $this->branchId = (string) $branch['id'];
                    $this->branchName = (string) $branch['name'];
                }
            }

            if ($branchId === (string) $branch['id']) {
                $this->isDefaultBranch = $branch['isDefault'];
                $this->branchName = (string) $branch['name'];
                $this->branchId = (string) $branch['id'];
            }
        }

        if ($this->isDefaultBranch === null) {
            throw new ClientException(sprintf('Can\'t resolve branchId: "%s".', $branchId));
        }
    }
}
