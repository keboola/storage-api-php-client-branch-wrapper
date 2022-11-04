<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Tests;

use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\DevBranches;
use Keboola\StorageApi\Options\BackendConfiguration;
use Keboola\StorageApiBranch\ClientWrapper;
use Keboola\StorageApiBranch\Factory\ClientOptions;
use LogicException;
use PHPUnit\Framework\TestCase;

class ClientWrapperTest extends TestCase
{
    public function testCreateBranch(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            '1234'
        ));
        self::assertInstanceOf(Client::class, $clientWrapper->getBasicClient());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClientIfAvailable());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClient());
        self::assertTrue($clientWrapper->hasBranch());
    }

    public function testCreateNoBranch(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            null
        ));
        self::assertInstanceOf(Client::class, $clientWrapper->getBasicClient());
        self::assertInstanceOf(Client::class, $clientWrapper->getBranchClientIfAvailable());
        self::assertFalse($clientWrapper->hasBranch());
        self::assertNull($clientWrapper->getBranchName());
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Branch is not set');
        $clientWrapper->getBranchClient();
    }

    public function testCreateDefaultBranch(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            ClientWrapper::BRANCH_DEFAULT
        ));
        self::assertInstanceOf(Client::class, $clientWrapper->getBasicClient());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClientIfAvailable());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClient());
        self::assertTrue($clientWrapper->hasBranch());
    }

    public function testGetClientOptions(): void
    {
        $options = new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN')
        );
        $clientWrapper = new ClientWrapper($options);
        self::assertSame(
            getenv('TEST_STORAGE_API_URL'),
            $clientWrapper->getClientOptionsReadOnly()->getUrl()
        );
    }

    public function testGetBranchName(): void
    {
        $client = new Client(
            [
                'url' => (string) getenv('TEST_STORAGE_API_URL'),
                'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
            ]
        );
        $branchesApi = new DevBranches($client);
        $branchId = (string) $branchesApi->createBranch('ClientWrapperTest::testGetBranchName')['id'];
        try {
            $clientWrapper = new ClientWrapper(new ClientOptions(
                (string) getenv('TEST_STORAGE_API_URL'),
                (string) getenv('TEST_STORAGE_API_TOKEN'),
                $branchId
            ));
            self::assertSame($branchId, $clientWrapper->getBranchId());
            self::assertSame('ClientWrapperTest::testGetBranchName', $clientWrapper->getBranchName());
        } finally {
            $branchesApi->deleteBranch((int) $branchId);
        }
    }

    public function testClientsAreProperlyConfigured(): void
    {
        $backendConfiguration = new BackendConfiguration('123-transfomration', 'small');

        $clientOptionsMock = $this->createMock(ClientOptions::class);
        $clientOptionsMock->expects(self::exactly(2))
            ->method('getClientConstructOptions')
            ->willReturn([
                'url' => 'dummy',
                'token' => 'dummy-token',
            ]);

        $clientOptionsMock->expects(self::exactly(2))
            ->method('getRunId')
            ->willReturn('124');

        $clientOptionsMock->expects(self::exactly(2))
            ->method('getBackendConfiguration')
            ->willReturn($backendConfiguration);

        $clientOptionsMock->expects(self::exactly(3))
            ->method('getBranchId')
            ->willReturn('dummy-branch');

        $clientWrapper = new ClientWrapper($clientOptionsMock);

        $basicClient = $clientWrapper->getBasicClient();
        self::assertInstanceOf(Client::class, $basicClient);
        self::assertSame('dummy', $basicClient->getApiUrl());
        self::assertSame('dummy-token', $basicClient->getTokenString());
        self::assertSame('124', $basicClient->getRunId());
        self::assertSame($backendConfiguration, $basicClient->getBackendConfiguration());
        self::assertSame($basicClient, $clientWrapper->getBasicClient());

        $branchClient = $clientWrapper->getBranchClient();
        self::assertInstanceOf(BranchAwareClient::class, $branchClient);
        self::assertSame('dummy', $branchClient->getApiUrl());
        self::assertSame('dummy-token', $branchClient->getTokenString());
        self::assertSame('124', $branchClient->getRunId());
        self::assertSame($backendConfiguration, $branchClient->getBackendConfiguration());
        self::assertSame($branchClient, $clientWrapper->getBranchClient());
    }
}
