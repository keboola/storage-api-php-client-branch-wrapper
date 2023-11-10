<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Tests;

use Generator;
use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\DevBranches;
use Keboola\StorageApi\Options\BackendConfiguration;
use Keboola\StorageApiBranch\ClientWrapper;
use Keboola\StorageApiBranch\Factory\ClientOptions;
use LogicException;
use PHPUnit\Framework\TestCase;

class ClientWrapperTest extends TestCase
{
    public function testGetClientBasic(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            runId: '1234',
            backendConfiguration: new BackendConfiguration('foo', 'bar'),
        ));
        self::assertInstanceOf(Client::class, $clientWrapper->getBasicClient());
        self::assertSame('1234', $clientWrapper->getBasicClient()->getRunId());
        self::assertEquals(
            new BackendConfiguration('foo', 'bar'),
            $clientWrapper->getBasicClient()->getBackendConfiguration(),
        );
        self::assertTrue($clientWrapper->isDefaultBranch());
    }

    public function testGetClientBranch(): void
    {
        $branchApi = new DevBranches(new Client([
            'url' => (string) getenv('TEST_STORAGE_API_URL'),
            'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
        ]));
        $branchId = $branchApi->createBranch(uniqid('testCreateBranch'))['id'];

        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            (string) $branchId,
        ));
        self::assertInstanceOf(Client::class, $clientWrapper->getBasicClient());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClient());
        self::assertTrue($clientWrapper->isDevelopmentBranch());
        self::assertSame((string) $branchId, $clientWrapper->getBranchId());
        self::assertFalse($clientWrapper->isDefaultBranch());

        $branchApi->deleteBranch($branchId);
    }

    public function testGetClientNoBranch(): void
    {
        $expectedId = null;
        $branchApi = new DevBranches(new Client([
            'url' => (string) getenv('TEST_STORAGE_API_URL'),
            'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
        ]));
        foreach ($branchApi->listBranches() as $branch) {
            if ($branch['isDefault']) {
                $expectedId = $branch['id'];
                break;
            }
        }

        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            null,
        ));
        self::assertInstanceOf(Client::class, $clientWrapper->getBasicClient());
        self::assertFalse($clientWrapper->isDevelopmentBranch());
        self::assertSame('Main', $clientWrapper->getBranchName());
        self::assertSame((string) $expectedId, $clientWrapper->getBranchId());
        self::assertTrue($clientWrapper->isDefaultBranch());
    }

    public function testGetClientDefaultBranch(): void
    {
        $expectedId = null;
        $branchApi = new DevBranches(new Client([
            'url' => (string) getenv('TEST_STORAGE_API_URL'),
            'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
        ]));
        foreach ($branchApi->listBranches() as $branch) {
            if ($branch['isDefault']) {
                $expectedId = $branch['id'];
                break;
            }
        }

        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            ClientWrapper::BRANCH_DEFAULT,
        ));
        self::assertInstanceOf(Client::class, $clientWrapper->getBasicClient());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClient());
        self::assertFalse($clientWrapper->isDevelopmentBranch());
        self::assertSame('Main', $clientWrapper->getBranchName());
        self::assertSame((string) $expectedId, $clientWrapper->getBranchId());
    }

    public function testGetClientOptions(): void
    {
        $options = new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
        );
        $clientWrapper = new ClientWrapper($options);
        self::assertSame(
            getenv('TEST_STORAGE_API_URL'),
            $clientWrapper->getClientOptionsReadOnly()->getUrl(),
        );
    }

    public function testGetBranchName(): void
    {
        $client = new Client(
            [
                'url' => (string) getenv('TEST_STORAGE_API_URL'),
                'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
            ],
        );
        $branchesApi = new DevBranches($client);
        $branchId = (string) $branchesApi->createBranch('ClientWrapperTest::testGetBranchName')['id'];
        try {
            $clientWrapper = new ClientWrapper(new ClientOptions(
                (string) getenv('TEST_STORAGE_API_URL'),
                (string) getenv('TEST_STORAGE_API_TOKEN'),
                $branchId,
            ));
            self::assertSame($branchId, $clientWrapper->getBranchId());
            self::assertSame('ClientWrapperTest::testGetBranchName', $clientWrapper->getBranchName());
        } finally {
            $branchesApi->deleteBranch((int) $branchId);
        }
    }

    public function testClientsAreProperlyConfigured(): void
    {
        $mainBranchId = null;
        $branchApi = new DevBranches(new Client([
            'url' => (string) getenv('TEST_STORAGE_API_URL'),
            'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
        ]));
        foreach ($branchApi->listBranches() as $branch) {
            if ($branch['isDefault']) {
                $mainBranchId = $branch['id'];
                break;
            }
        }

        $backendConfiguration = new BackendConfiguration('123-transformation', 'small');

        $clientOptionsMock = $this->createMock(ClientOptions::class);
        $clientOptionsMock->expects(self::exactly(2))
            ->method('getClientConstructOptions')
            ->willReturn([
                'url' => (string) getenv('TEST_STORAGE_API_URL'),
                'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
            ]);

        $clientOptionsMock->expects(self::exactly(2))
            ->method('getRunId')
            ->willReturn('124');

        $clientOptionsMock->expects(self::exactly(2))
            ->method('getBackendConfiguration')
            ->willReturn($backendConfiguration);

        $clientOptionsMock->expects(self::exactly(1))
            ->method('getBranchId')
            ->willReturn((string) $mainBranchId);

        $clientWrapper = new ClientWrapper($clientOptionsMock);

        $basicClient = $clientWrapper->getBasicClient();
        self::assertInstanceOf(Client::class, $basicClient);
        self::assertSame((string) getenv('TEST_STORAGE_API_URL'), $basicClient->getApiUrl());
        self::assertSame((string) getenv('TEST_STORAGE_API_TOKEN'), $basicClient->getTokenString());
        self::assertSame('124', $basicClient->getRunId());
        self::assertSame($backendConfiguration, $basicClient->getBackendConfiguration());
        // is cached
        self::assertSame($basicClient, $clientWrapper->getBasicClient());

        $branchClient = $clientWrapper->getBranchClient();
        self::assertInstanceOf(BranchAwareClient::class, $branchClient);
        self::assertSame((string) getenv('TEST_STORAGE_API_URL'), $branchClient->getApiUrl());
        self::assertSame((string) getenv('TEST_STORAGE_API_TOKEN'), $branchClient->getTokenString());
        self::assertSame('124', $branchClient->getRunId());
        self::assertSame($backendConfiguration, $branchClient->getBackendConfiguration());
        // is cached
        self::assertSame($branchClient, $clientWrapper->getBranchClient());

        $tableClient = $clientWrapper->getTableAndFileStorageClient();
        self::assertInstanceOf(Client::class, $tableClient);
        self::assertSame((string) getenv('TEST_STORAGE_API_URL'), $tableClient->getApiUrl());
        self::assertSame((string) getenv('TEST_STORAGE_API_TOKEN'), $tableClient->getTokenString());
        self::assertSame('124', $tableClient->getRunId());
        self::assertSame($backendConfiguration, $tableClient->getBackendConfiguration());
        // is cached
        self::assertSame($tableClient, $clientWrapper->getTableAndFileStorageClient());
        self::assertSame($basicClient, $clientWrapper->getTableAndFileStorageClient());
    }

    /**
     * @param class-string $expectedClassName
     * @dataProvider useBranchStorageDataProvider
     */
    public function testCreateBranchStorage(?bool $useBranchStorage, string $expectedClassName): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
            token: (string) getenv('TEST_STORAGE_API_TOKEN'),
            branchId: ClientWrapper::BRANCH_DEFAULT,
            useBranchStorage: $useBranchStorage,
        ));
        self::assertInstanceOf(Client::class, $clientWrapper->getBasicClient());
        self::assertInstanceOf(BranchAwareClient::class, $clientWrapper->getBranchClient());
        self::assertInstanceOf($expectedClassName, $clientWrapper->getTableAndFileStorageClient());
        self::assertFalse($clientWrapper->isDevelopmentBranch());
    }

    public function useBranchStorageDataProvider(): Generator
    {
        yield 'useBranchStorage is null' => [
            'useBranchStorage' => null,
            'expectedClassName' => Client::class,
        ];
        yield 'useBranchStorage is true' => [
            'useBranchStorage' => true,
            'expectedClassName' => BranchAwareClient::class,
        ];
        yield 'useBranchStorage is false' => [
            'useBranchStorage' => false,
            'expectedClassName' => Client::class,
        ];
    }

    public function testCreateBranchStorageClientNoBranch(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
            token: (string) getenv('TEST_STORAGE_API_TOKEN'),
            branchId: null,
            useBranchStorage: true,
        ));
        self::assertInstanceOf(Client::class, $clientWrapper->getBasicClient());
        self::assertFalse($clientWrapper->isDevelopmentBranch());
        self::assertSame('Main', $clientWrapper->getBranchName());
        self::assertInstanceOf(Client::class, $clientWrapper->getTableAndFileStorageClient());
    }

    public function testGetDefaultBranch(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
            token: (string) getenv('TEST_STORAGE_API_TOKEN'),
            branchId: null,
            useBranchStorage: true,
        ));

        self::assertIsScalar($clientWrapper->getDefaultBranch()->id);
        self::assertSame('Main', $clientWrapper->getDefaultBranch()->name);
        self::assertTrue($clientWrapper->getDefaultBranch()->isDefault);
    }

    public function testGetConfiguredBranch(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
            token: (string) getenv('TEST_STORAGE_API_TOKEN'),
            branchId: null,
            useBranchStorage: true,
        ));

        self::assertIsScalar($clientWrapper->getBranch()->id);
        self::assertSame('Main', $clientWrapper->getDefaultBranch()->name);
        self::assertTrue($clientWrapper->getBranch()->isDefault);
    }

    public function testGetDefaultBranchIsCached(): void
    {
        $storageClient = $this->createMock(Client::class);
        $storageClient
            ->expects(self::once()) // this is important, list branches must be only called once
            ->method('apiGet')
            ->with('dev-branches/')
            ->willReturn([
                [
                    'id' => '123',
                    'name' => 'Main',
                    'isDefault' => true,
                ],
                [
                    'id' => '124',
                    'name' => 'Dev',
                    'isDefault' => false,
                ],
            ]);

        $clientWrapper = $this->getMockBuilder(ClientWrapper::class)
            ->setConstructorArgs([
                new ClientOptions(
                    url: (string) getenv('TEST_STORAGE_API_URL'),
                    token: (string) getenv('TEST_STORAGE_API_TOKEN'),
                    branchId: null,
                    useBranchStorage: true,
                ),
            ])
            ->onlyMethods(['getBasicClient'])
            ->getMock();

        $clientWrapper->method('getBasicClient')->willReturn($storageClient);

        $clientWrapper->getDefaultBranch();
        $defaultBranch = $clientWrapper->getDefaultBranch();

        self::assertSame('123', $defaultBranch->id);
        self::assertSame('Main', $defaultBranch->name);
        self::assertTrue($defaultBranch->isDefault);
    }

    public function testBranchesInvalidDefault(): void
    {
        $storageClient = $this->createMock(Client::class);
        $storageClient
            ->expects(self::once()) // this is important, list branches must be only called once
            ->method('apiGet')
            ->with('dev-branches/')
            ->willReturn([
                [
                    'id' => '123',
                    'name' => 'WAT',
                    'isDefault' => false,
                ],
                [
                    'id' => '124',
                    'name' => 'Dev',
                    'isDefault' => false,
                ],
            ]);

        $clientWrapper = $this->getMockBuilder(ClientWrapper::class)
            ->setConstructorArgs([
                new ClientOptions(
                    url: (string) getenv('TEST_STORAGE_API_URL'),
                    token: (string) getenv('TEST_STORAGE_API_TOKEN'),
                    branchId: null,
                    useBranchStorage: true,
                ),
            ])
            ->onlyMethods(['getBasicClient'])
            ->getMock();

        $clientWrapper->method('getBasicClient')->willReturn($storageClient);
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Can not find default branch for branchId: "".');
        $clientWrapper->getDefaultBranch();
    }


    public function testBranchesInvalidBranch(): void
    {
        $storageClient = $this->createMock(Client::class);
        $storageClient
            ->expects(self::once()) // this is important, list branches must be only called once
            ->method('apiGet')
            ->with('dev-branches/')
            ->willReturn([
                [
                    'id' => '123',
                    'name' => 'Main',
                    'isDefault' => true,
                ],
                [
                    'id' => '124',
                    'name' => 'Dev',
                    'isDefault' => false,
                ],
            ]);

        $clientWrapper = $this->getMockBuilder(ClientWrapper::class)
            ->setConstructorArgs([
                new ClientOptions(
                    url: (string) getenv('TEST_STORAGE_API_URL'),
                    token: (string) getenv('TEST_STORAGE_API_TOKEN'),
                    branchId: '125',
                    useBranchStorage: true,
                ),
            ])
            ->onlyMethods(['getBasicClient'])
            ->getMock();

        $clientWrapper->method('getBasicClient')->willReturn($storageClient);
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Can not resolve branchId: "125".');
        $clientWrapper->getDefaultBranch();
    }

    public function testBranchesGetClientForBranch(): void
    {
        $branchApi = new DevBranches(new Client([
            'url' => (string) getenv('TEST_STORAGE_API_URL'),
            'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
        ]));
        $branchId = (string) $branchApi->createBranch(uniqid('testCreateBranch'))['id'];
        $defaultBranchId = (string) $branchApi->getDefaultBranch()['id'];

        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
        ));

        $defaultBranchClient = $clientWrapper->getClientForBranch($defaultBranchId);
        $devBranchClient = $clientWrapper->getClientForBranch($branchId);
        // branch client is for the configured for the default branch
        self::assertSame($defaultBranchClient, $clientWrapper->getBranchClient());
        self::assertSame($devBranchClient, $clientWrapper->getClientForBranch($branchId));
        self::assertSame($defaultBranchClient, $clientWrapper->getClientForDefaultBranch());

        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            (string) $branchId,
        ));

        $defaultBranchClient = $clientWrapper->getClientForBranch($defaultBranchId);
        $devBranchClient = $clientWrapper->getClientForBranch($branchId);
        // branch client is for the configured branch in client wrapper
        self::assertSame($devBranchClient, $clientWrapper->getBranchClient());
        self::assertSame($defaultBranchClient, $clientWrapper->getClientForBranch($defaultBranchId));
        self::assertSame($defaultBranchClient, $clientWrapper->getClientForDefaultBranch());

        $branchApi->deleteBranch((int) $branchId);
    }

    public function testBranchesGetClientForBranchDefaultRejected(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
        ));

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Branch ID must be a number. "default" given.');
        $clientWrapper->getClientForBranch('default');
    }

    public function testGetClientForNonExistingBranchRejected(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
        ));

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Branch with ID "1" does not exist.');
        $clientWrapper->getClientForBranch('1');
    }

    public function testGetToken(): void
    {
        $clientWrapper = new ClientWrapper(new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
        ));

        self::assertSame(
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            $clientWrapper->getToken()->getTokenValue(),
        );
    }
}
