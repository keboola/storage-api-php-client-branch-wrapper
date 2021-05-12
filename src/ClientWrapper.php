<?php

namespace Keboola\StorageApiBranch;

use Closure;
use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\DevBranches;
use Psr\Log\LoggerInterface;

class ClientWrapper
{
    const BRANCH_UNINITIALIZED = null;
    const BRANCH_MAIN = '';

    /** @var Client */
    private $client;

    /** @var null|string */
    private $branchId;

    /** @var BranchAwareClient */
    private $branchClient;

    /** @var ?Closure */
    private $pollDelayFunction;

    /** @var ?LoggerInterface */
    private $logger;

    public function __construct(
        Client $storageClient,
        $pollDelayFunction,
        $logger,
        $branchId = self::BRANCH_UNINITIALIZED
    ) {
        $this->client = $storageClient;
        $this->pollDelayFunction = $pollDelayFunction;
        $this->logger = $logger;
        $this->branchId = $branchId;
    }

    public function setBranchId($branchId)
    {
        if ($this->branchId !== self::BRANCH_UNINITIALIZED) {
            throw new \LogicException('Branch can only be set once.');
        }
        $this->branchId = $branchId;
    }

    public function getBasicClient()
    {
        return $this->client;
    }

    public function getBranchClient()
    {
        $this->validateSelf();
        if (!$this->branchClient) {
            $this->branchClient = new BranchAwareClient(
                $this->branchId,
                [
                    'url' => $this->client->getApiUrl(),
                    'token' => $this->client->getTokenString(),
                    'userAgent' => $this->client->getUserAgent(),
                    'backoffMaxTries' => $this->client->getBackoffMaxTries(),
                    'jobPollRetryDelay' => $this->pollDelayFunction,
                    'logger' => $this->logger,
                ]
            );
            if ($this->client->getRunId()) {
                $this->branchClient->setRunId($this->client->getRunId());
            }
        }
        return $this->branchClient;
    }

    /**
     * Returns branchClient if a branch was configured and basicClient otherwise.
     *
     * @return Client
     */
    public function getBranchClientIfAvailable()
    {
        if ($this->hasBranch()) {
            return $this->getBranchClient();
        }

        return $this->getBasicClient();
    }

    public function getBranchId()
    {
        $this->validateSelf();
        return $this->branchId;
    }

    public function getBranchName()
    {
        $this->validateSelf();
        if ($this->hasBranch()) {
            $branches = new DevBranches($this->getBasicClient());
            return $branches->getBranch($this->getBranchId())['name'];
        } else {
            return null;
        }
    }

    public function hasBranch()
    {
        $this->validateSelf();
        return $this->branchId !== self::BRANCH_MAIN;
    }

    private function validateSelf()
    {
        if ($this->branchId === self::BRANCH_UNINITIALIZED) {
            throw new \LogicException('Wrapper not initialized properly.');
        }
    }
}
