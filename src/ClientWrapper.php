<?php

namespace Keboola\StorageApiBranch;

use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;

class ClientWrapper
{
    /** @var Client */
    private $client;

    /** @var string */
    private $branchId = null;

    /** @var BranchAwareClient */
    private $branchClient;

    public function __construct(Client $storageClient)
    {
        $this->client = $storageClient;
    }

    public function setBranch($branchId)
    {
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
                ]
            );
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
