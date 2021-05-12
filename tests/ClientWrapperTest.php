<?php

namespace Keboola\StorageApiBranch\Tests;

use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\DevBranches;
use Keboola\StorageApiBranch\ClientWrapper;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use ReflectionProperty;

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
        $clientWrapper = new ClientWrapper($client, null, null);
        self::assertSame($client, $clientWrapper->getBasicClient());
    }

    public function testIncompleteInitGetBranch()
    {
        $clientWrapper = new ClientWrapper($this->getClient(), null, null);
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Wrapper not initialized properly.');
        $clientWrapper->getBranchId();
    }

    public function testIncompleteInitGetBranchClient()
    {
        $clientWrapper = new ClientWrapper($this->getClient(), null, null);
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Wrapper not initialized properly.');
        $clientWrapper->getBranchClient();
    }

    public function testSetBranchNoBranch()
    {
        $clientWrapper = new ClientWrapper($this->getClient(), null, null);
        $clientWrapper->setBranchId(ClientWrapper::BRANCH_MAIN);
        self::assertSame(ClientWrapper::BRANCH_MAIN, $clientWrapper->getBranchId());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClient());
        self::assertFalse($clientWrapper->hasBranch());
        self::assertNull($clientWrapper->getBranchName());
    }

    public function testSetBranch()
    {
        $clientWrapper = new ClientWrapper($this->getClient(), null, null);
        $clientWrapper->setBranchId('dev-123');
        self::assertSame('dev-123', $clientWrapper->getBranchId());
        $client = $clientWrapper->getBranchClient();
        self::assertInstanceOf(BranchAwareClient::class, $client);
        self::assertSame($client, $clientWrapper->getBranchClient());
        self::assertTrue($clientWrapper->hasBranch());
    }
    
    public function testSetBranchTwice()
    {
        $clientWrapper = new ClientWrapper($this->getClient(), null, null);
        $clientWrapper->setBranchId('dev-123');
        self::assertSame('dev-123', $clientWrapper->getBranchId());
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Branch can only be set once');
        $clientWrapper->setBranchId('dev-321');
    }

    public function testCreateOptions()
    {
        $client = $this->getClient();
        $logger = new TestLogger();
        $delayFunction = function () {
            echo 'boo';
        };
        $clientWrapper = new ClientWrapper($client, $delayFunction, $logger, 'branch123');
        self::assertSame($client, $clientWrapper->getBasicClient());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClient());
        $branchClient = $clientWrapper->getBranchClient();
        $reflection = new ReflectionProperty(Client::class, 'jobPollRetryDelay');
        $reflection->setAccessible(true);
        self::assertSame($delayFunction, $reflection->getValue($branchClient));
        $branchClient = $clientWrapper->getBranchClient();
        $reflection = new ReflectionProperty(Client::class, 'logger');
        $reflection->setAccessible(true);
        self::assertEquals($logger, $reflection->getValue($branchClient));
        self::assertSame('branch123', $clientWrapper->getBranchId());
    }

    public function testCreateEmptyOptions()
    {
        $client = $this->getClient();
        $clientWrapper = new ClientWrapper($client, null, null, 'branch123');
        self::assertSame($client, $clientWrapper->getBasicClient());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClient());
        $branchClient = $clientWrapper->getBranchClient();
        $reflection = new ReflectionProperty(Client::class, 'jobPollRetryDelay');
        $reflection->setAccessible(true);
        self::assertNotNull($reflection->getValue($branchClient));
        $branchClient = $clientWrapper->getBranchClient();
        $reflection = new ReflectionProperty(Client::class, 'logger');
        $reflection->setAccessible(true);
        self::assertNotNull($reflection->getValue($branchClient));
        self::assertSame('branch123', $clientWrapper->getBranchId());
    }

    public function testGetBranchName()
    {
        $branches = new DevBranches($this->getClient());
        foreach ($branches->listBranches() as $branch) {
            if ($branch['name'] === 'dev-123') {
                $branches->deleteBranch($branch['id']);
            }
        }
        $branchId = $branches->createBranch('dev-123')['id'];
        $clientWrapper = new ClientWrapper($this->getClient(), null, null);
        $clientWrapper->setBranchId($branchId);
        self::assertSame($branchId, $clientWrapper->getBranchId());
        self::assertSame('dev-123', $clientWrapper->getBranchName());
    }

    public function testGetBranchClientIfAvailableWithNoBranchConfigured()
    {
        $client = $this->getClient();
        $clientWrapper = new ClientWrapper($client, null, null, ClientWrapper::BRANCH_MAIN);

        self::assertSame($client, $clientWrapper->getBranchClientIfAvailable());
    }

    public function testGetBranchClientIfAvailableWithBranchConfigured()
    {
        $client = $this->getClient();
        $clientWrapper = new ClientWrapper($client, null, null, 'test');

        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClientIfAvailable());
    }
}
