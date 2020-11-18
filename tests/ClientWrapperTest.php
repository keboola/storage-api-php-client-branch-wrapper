<?php

namespace Keboola\StorageApiBranch\Tests;

use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApiBranch\ClientWrapper;
use LogicException;
use PHPUnit\Framework\TestCase;

class ClientWrapperTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $requiredEnvs = ['TEST_STORAGE_API_URL', 'TEST_STORAGE_API_TOKEN'];
        foreach ($requiredEnvs as $env) {
            if (empty($env)) {
                throw new LogicException(sprintf('Required env "%s" is empty.', $env));
            }
        }
    }

    private function getClient()
    {
        return new Client(
            [
                'url' => getenv('TEST_STORAGE_API_URL'),
                'token' => getenv('TEST_STORAGE_API_TOKEN'),
            ]

        );
    }

    public function testCreate()
    {
        $client = $this->getClient();
        $clientWrapper = new ClientWrapper($client);
        self::assertSame($client, $clientWrapper->getBasicClient());
    }

    public function testIncompleteInitGetBranch()
    {
        $clientWrapper = new ClientWrapper($this->getClient());
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Wrapper not initialized properly.');
        $clientWrapper->getBranch();
    }

    public function testIncompleteInitGetBranchClient()
    {
        $clientWrapper = new ClientWrapper($this->getClient());
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Wrapper not initialized properly.');
        $clientWrapper->getBranchClient();
    }

    public function testSetBranchNoBranch()
    {
        $clientWrapper = new ClientWrapper($this->getClient());
        $clientWrapper->setBranch('');
        self::assertSame('', $clientWrapper->getBranch());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClient());
        self::assertFalse($clientWrapper->hasBranch());
    }

    public function testSetBranch()
    {
        $clientWrapper = new ClientWrapper($this->getClient());
        $clientWrapper->setBranch('dev-123');
        self::assertSame('dev-123', $clientWrapper->getBranch());
        $client = $clientWrapper->getBranchClient();
        self::assertInstanceOf(BranchAwareClient::class, $client);
        self::assertSame($client, $clientWrapper->getBranchClient());
        self::assertTrue($clientWrapper->hasBranch());
    }

    public function testSetBranchTwice()
    {
        $clientWrapper = new ClientWrapper($this->getClient());
        $clientWrapper->setBranch('dev-123');
        self::assertSame('dev-123', $clientWrapper->getBranch());
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Branch can only be set once');
        $clientWrapper->setBranch('dev-321');
    }
}
