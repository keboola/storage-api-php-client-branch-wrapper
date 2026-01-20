<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Tests\Factory;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApiBranch\Factory\ClientOptions;
use Keboola\StorageApiBranch\Factory\StorageClientRequestFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class StorageClientRequestFactoryTest extends TestCase
{
    private const TOKEN_HEADER = 'HTTP_X_STORAGEAPI_TOKEN';
    private const RUN_ID_HEADER = 'HTTP_X_KBC_RUNID';
    private const AUTHORIZATION_HEADER = 'HTTP_AUTHORIZATION';

    /**
     * @dataProvider provideEmptyTokenHeader
     */
    public function testFactoryRequiresTokenHeader(array $server): void
    {
        $request = new Request([], [], [], [], [], $server);
        $factory = new StorageClientRequestFactory(new ClientOptions());

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(
            'Storage API token must be supplied in Authorization header or X-StorageApi-Token header.',
        );
        $this->expectExceptionCode(401);

        $factory->createClientWrapper($request);
    }

    public function provideEmptyTokenHeader(): iterable
    {
        yield 'no header' => [
            [],
        ];

        yield 'empty header' => [
            [self::TOKEN_HEADER => ''],
        ];
    }

    public function testFactoryCreatesWorkingClient(): void
    {
        $request = new Request([], [], [], [], [], [
            self::TOKEN_HEADER => $_SERVER['TEST_STORAGE_API_TOKEN'],
        ]);

        $factory = new StorageClientRequestFactory(new ClientOptions($_SERVER['TEST_STORAGE_API_URL']));

        $clientWrapper = $factory->createClientWrapper($request);
        $tokenInfo = $clientWrapper->getBasicClient()->verifyToken();

        self::assertArrayHasKey('owner', $tokenInfo);
        self::assertStringStartsWith('run-', (string) $clientWrapper->getClientOptionsReadOnly()->getRunId());
    }

    public function testFactoryRunIdPresent(): void
    {
        $request = new Request([], [], [], [], [], [
            self::TOKEN_HEADER => $_SERVER['TEST_STORAGE_API_TOKEN'],
            self::RUN_ID_HEADER => '123',
        ]);

        $factory = new StorageClientRequestFactory(new ClientOptions($_SERVER['TEST_STORAGE_API_URL']));
        $clientWrapper = $factory->createClientWrapper($request);
        self::assertEquals('123', $clientWrapper->getClientOptionsReadOnly()->getRunId());
    }

    public function testFactoryRunIdNotPresent(): void
    {
        $request = new Request([], [], [], [], [], [
            self::TOKEN_HEADER => $_SERVER['TEST_STORAGE_API_TOKEN'],
        ]);

        $factory = new StorageClientRequestFactory(new ClientOptions($_SERVER['TEST_STORAGE_API_URL']));
        $clientWrapper = $factory->createClientWrapper($request);
        self::assertStringStartsWith('run-', (string) $clientWrapper->getClientOptionsReadOnly()->getRunId());
    }

    public function testFactoryRunIdNotPresentGeneratorSet(): void
    {
        $request = new Request([], [], [], [], [], [
            self::TOKEN_HEADER => $_SERVER['TEST_STORAGE_API_TOKEN'],
        ]);
        $clientOptions = new ClientOptions($_SERVER['TEST_STORAGE_API_URL']);
        $clientOptions->setRunIdGenerator(function (ClientOptions $clientOptions) {
            return 'foo-' . $clientOptions->getUrl();
        });
        $factory = new StorageClientRequestFactory($clientOptions);
        $clientWrapper = $factory->createClientWrapper($request);
        self::assertStringStartsWith('foo-http', (string) $clientWrapper->getClientOptionsReadOnly()->getRunId());
    }

    public function testFactoryRunIdPresentGeneratorSet(): void
    {
        $request = new Request([], [], [], [], [], [
            self::TOKEN_HEADER => $_SERVER['TEST_STORAGE_API_TOKEN'],
            self::RUN_ID_HEADER => '123',
        ]);
        $clientOptions = new ClientOptions($_SERVER['TEST_STORAGE_API_URL']);
        $clientOptions->setRunIdGenerator(function (ClientOptions $clientOptions) {
            return 'foo-' . $clientOptions->getUrl();
        });
        $factory = new StorageClientRequestFactory($clientOptions);
        $clientWrapper = $factory->createClientWrapper($request);
        self::assertStringStartsWith('123', (string) $clientWrapper->getClientOptionsReadOnly()->getRunId());
    }

    public function testExtraClientOptions(): void
    {
        $request = new Request([], [], [], [], [], [
            self::TOKEN_HEADER => $_SERVER['TEST_STORAGE_API_TOKEN'],
        ]);
        $factory = new StorageClientRequestFactory(new ClientOptions($_SERVER['TEST_STORAGE_API_URL']));
        $clientWrapper = $factory->createClientWrapper($request, new ClientOptions(branchId: '1234'));

        self::assertSame('1234', $clientWrapper->getClientOptionsReadOnly()->getBranchId());
    }

    public function testGetClientOptions(): void
    {
        $factory = new StorageClientRequestFactory(new ClientOptions('https://foo'));
        self::assertSame('https://foo', $factory->getClientOptionsReadOnly()->getUrl());
        $factory->getClientOptionsReadOnly()->setUrl('https://bar');
        self::assertSame('https://foo', $factory->getClientOptionsReadOnly()->getUrl());
    }

    public function testClientOptionsNotModified(): void
    {
        $request = new Request([], [], [], [], [], [
            self::TOKEN_HEADER => $_SERVER['TEST_STORAGE_API_TOKEN'],
        ]);
        $options = new ClientOptions($_SERVER['TEST_STORAGE_API_URL']);
        $factory = new StorageClientRequestFactory($options);
        $factory->createClientWrapper($request);
        self::assertNull($options->getToken());
        self::assertNull($factory->getClientOptionsReadOnly()->getToken());
    }

    public function testFactoryWithBearerToken(): void
    {
        $testToken = 'test-bearer-token-12345';
        $request = new Request([], [], [], [], [], [
            self::AUTHORIZATION_HEADER => 'Bearer ' . $testToken,
        ]);

        $factory = new StorageClientRequestFactory(new ClientOptions($_SERVER['TEST_STORAGE_API_URL']));
        $clientWrapper = $factory->createClientWrapper($request);

        self::assertSame($testToken, $clientWrapper->getClientOptionsReadOnly()->getToken());
        self::assertSame(Client::AUTH_TYPE_BEARER, $clientWrapper->getClientOptionsReadOnly()->getAuthType());
    }

    public function testFactoryWithStorageToken(): void
    {
        $request = new Request([], [], [], [], [], [
            self::TOKEN_HEADER => $_SERVER['TEST_STORAGE_API_TOKEN'],
        ]);

        $factory = new StorageClientRequestFactory(new ClientOptions($_SERVER['TEST_STORAGE_API_URL']));
        $clientWrapper = $factory->createClientWrapper($request);

        self::assertSame($_SERVER['TEST_STORAGE_API_TOKEN'], $clientWrapper->getClientOptionsReadOnly()->getToken());
        self::assertSame(
            Client::AUTH_TYPE_STORAGE_TOKEN,
            $clientWrapper->getClientOptionsReadOnly()->getAuthType(),
        );
    }

    public function testFactoryBearerTokenHasPriorityOverStorageToken(): void
    {
        $bearerToken = 'bearer-token-12345';
        $request = new Request([], [], [], [], [], [
            self::AUTHORIZATION_HEADER => 'Bearer ' . $bearerToken,
            self::TOKEN_HEADER => $_SERVER['TEST_STORAGE_API_TOKEN'],
        ]);

        $factory = new StorageClientRequestFactory(new ClientOptions($_SERVER['TEST_STORAGE_API_URL']));
        $clientWrapper = $factory->createClientWrapper($request);

        self::assertSame($bearerToken, $clientWrapper->getClientOptionsReadOnly()->getToken());
        self::assertSame(Client::AUTH_TYPE_BEARER, $clientWrapper->getClientOptionsReadOnly()->getAuthType());
    }

    public function testFactoryWithInvalidAuthorizationHeader(): void
    {
        $request = new Request([], [], [], [], [], [
            self::AUTHORIZATION_HEADER => 'Basic sometoken',
        ]);

        $factory = new StorageClientRequestFactory(new ClientOptions());

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(
            'Storage API token must be supplied in Authorization header or X-StorageApi-Token header.',
        );
        $this->expectExceptionCode(401);

        $factory->createClientWrapper($request);
    }

    public function testFactoryWithEmptyBearerToken(): void
    {
        $request = new Request([], [], [], [], [], [
            self::AUTHORIZATION_HEADER => 'Bearer ',
        ]);

        $factory = new StorageClientRequestFactory(new ClientOptions($_SERVER['TEST_STORAGE_API_URL']));
        $clientWrapper = $factory->createClientWrapper($request);

        self::assertSame('', $clientWrapper->getClientOptionsReadOnly()->getToken());
        self::assertSame(Client::AUTH_TYPE_BEARER, $clientWrapper->getClientOptionsReadOnly()->getAuthType());
    }
}
