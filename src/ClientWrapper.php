<?php

namespace Keboola\StorageApiBranch;

use Closure;
use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Psr\Log\LoggerInterface;

class ClientWrapper
{
    /** @var Client */
    private $client;

    /** @var string */
    private $branchId = null;

    /** @var BranchAwareClient */
    private $branchClient;

    /** @var ?Closure */
    private $pollDelayFunction;

    /** @var ?LoggerInterface */
    private $logger;

    public function __construct(Client $storageClient, $pollDelayFunction, $logger, $branchId = null)
    {
        $this->client = $storageClient;
        $this->pollDelayFunction = $pollDelayFunction;
        $this->logger = $logger;
        $this->branchId = $branchId;
    }

    public function setBranch($branchId)
    {
        if ($this->branchId !== null) {
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

    public function getBranch()
    {
        $this->validateSelf();
        return $this->branchId;
    }

    public function hasBranch()
    {
        $this->validateSelf();
        return $this->branchId !== '';
    }

    private function validateSelf()
    {
        if ($this->branchId === null) {
            throw new \LogicException('Wrapper not initialized properly.');
        }
    }
}
