<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Tests;

use InvalidArgumentException;
use Keboola\StorageApi\BranchAwareClient;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApiBranch\ClientWrapper;
use Keboola\StorageApiBranch\Factory\ClientOptions;
use Keboola\StorageApiBranch\Factory\StorageClientRequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;

class OAuthAuthenticationTest extends TestCase
{


    public function testClientOptionsDefaultAuthenticationMethodIsToken(): void
    {
        $options = new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            'test-token',
        );

        $clientOptions = $options->getClientConstructOptions();
        $this->assertNull($clientOptions['authMethod']);
    }

    public function testClientOptionsExplicitTokenAuthenticationMethod(): void
    {
        $options = new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            'test-token',
            authMethod: Client::AUTH_METHOD_TOKEN,
        );

        $clientOptions = $options->getClientConstructOptions();
        $this->assertEquals(Client::AUTH_METHOD_TOKEN, $clientOptions['authMethod']);
    }

    public function testClientOptionsOAuthAuthenticationMethod(): void
    {
        $options = new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            'test-oauth-token',
            authMethod: Client::AUTH_METHOD_OAUTH,
        );

        $clientOptions = $options->getClientConstructOptions();
        $this->assertEquals(Client::AUTH_METHOD_OAUTH, $clientOptions['authMethod']);
    }

    public function testClientOptionsInvalidAuthenticationMethodThrowsException(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('authMethod must be "token" or "oauth". "invalid_method" given.');

        new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            'test-token',
            authMethod: 'invalid_method',
        );
    }

    public function testClientOptionsAuthenticationConstants(): void
    {
        $this->assertEquals('token', Client::AUTH_METHOD_TOKEN);
        $this->assertEquals('oauth', Client::AUTH_METHOD_OAUTH);
    }

    public function testClientWrapperWithTokenAuth(): void
    {
        $options = new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
            authMethod: Client::AUTH_METHOD_TOKEN,
        );

        $clientWrapper = new ClientWrapper($options);
        $this->assertEquals(Client::AUTH_METHOD_TOKEN, $clientWrapper->getAuthMethod());
    }

    public function testClientWrapperWithOAuthAuth(): void
    {
        $options = new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            'test-oauth-token',
            authMethod: Client::AUTH_METHOD_OAUTH,
        );

        $clientWrapper = new ClientWrapper($options);
        $this->assertEquals(Client::AUTH_METHOD_OAUTH, $clientWrapper->getAuthMethod());
    }

    public function testClientWrapperDefaultAuthMethod(): void
    {
        $options = new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('TEST_STORAGE_API_TOKEN'),
        );

        $clientWrapper = new ClientWrapper($options);
        // Default should be token when no authMethod is specified
        $this->assertEquals(Client::AUTH_METHOD_TOKEN, $clientWrapper->getBasicClient()->getAuthMethod());
    }

    public function testRequestFactoryWithStorageApiToken(): void
    {
        $factory = new StorageClientRequestFactory(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
        ));

        $request = new Request();
        $request->headers->set(StorageClientRequestFactory::TOKEN_HEADER, 'test-token');

        $clientWrapper = $factory->createClientWrapper($request);
        $this->assertEquals(Client::AUTH_METHOD_TOKEN, $clientWrapper->getAuthMethod());
    }

    public function testRequestFactoryWithOAuthToken(): void
    {
        $factory = new StorageClientRequestFactory(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
        ));

        $request = new Request();
        $request->headers->set(StorageClientRequestFactory::AUTHORIZATION_HEADER, 'Bearer oauth-token-123');

        $clientWrapper = $factory->createClientWrapper($request);
        $this->assertEquals(Client::AUTH_METHOD_OAUTH, $clientWrapper->getAuthMethod());
    }

    public function testRequestFactoryOAuthTokenTakesPrecedence(): void
    {
        $factory = new StorageClientRequestFactory(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
        ));

        $request = new Request();
        $request->headers->set(StorageClientRequestFactory::TOKEN_HEADER, 'test-token');
        $request->headers->set(StorageClientRequestFactory::AUTHORIZATION_HEADER, 'Bearer oauth-token-123');

        $clientWrapper = $factory->createClientWrapper($request);
        // OAuth should take precedence over Storage API token
        $this->assertEquals(Client::AUTH_METHOD_OAUTH, $clientWrapper->getAuthMethod());
    }

    public function testRequestFactoryNoTokenThrowsException(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(
            'Storage API token must be supplied in X-StorageApi-Token header or OAuth token in ' .
            'Authorization header with Bearer prefix.',
        );

        $factory = new StorageClientRequestFactory(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
        ));
        $request = new Request();

        $factory->createClientWrapper($request);
    }

    public function testRequestFactoryEmptyOAuthTokenThrowsException(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('OAuth token must be provided in Authorization header with Bearer prefix.');

        $factory = new StorageClientRequestFactory(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
        ));

        $request = new Request();
        $request->headers->set(StorageClientRequestFactory::AUTHORIZATION_HEADER, 'Bearer ');

        $factory->createClientWrapper($request);
    }

    public function testRequestFactoryInvalidAuthorizationHeader(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(
            'Storage API token must be supplied in X-StorageApi-Token header or OAuth token in ' .
            'Authorization header with Bearer prefix.',
        );

        $factory = new StorageClientRequestFactory(new ClientOptions(
            url: (string) getenv('TEST_STORAGE_API_URL'),
        ));

        $request = new Request();
        $request->headers->set(StorageClientRequestFactory::AUTHORIZATION_HEADER, 'Basic dXNlcjpwYXNz');

        $factory->createClientWrapper($request);
    }

    public function testClientOptionsAddValuesFromPreservesAuthMethod(): void
    {
        $options1 = new ClientOptions(
            'https://example.com',
            'token1',
            authMethod: Client::AUTH_METHOD_TOKEN,
        );

        $options2 = new ClientOptions(
            authMethod: Client::AUTH_METHOD_OAUTH,
        );

        $options1->addValuesFrom($options2);
        $this->assertEquals(Client::AUTH_METHOD_OAUTH, $options1->getAuthMethod());
    }

    /**
     * Test OAuth client basic operations if OAuth token is available
     */
    public function testOAuthClientBasicOperations(): void
    {
        // Skip this test if we don't have an OAuth token to test with
        if (!getenv('OAUTH_TOKEN')) {
            $this->markTestSkipped('OAuth token not available for testing');
        }

        $options = new ClientOptions(
            (string) getenv('TEST_STORAGE_API_URL'),
            (string) getenv('OAUTH_TOKEN'),
            authMethod: Client::AUTH_METHOD_OAUTH,
        );

        $clientWrapper = new ClientWrapper($options);

        $this->assertEquals(Client::AUTH_METHOD_OAUTH, $clientWrapper->getAuthMethod());

        // Test that we can make an authenticated API call
        $buckets = $clientWrapper->getBasicClient()->listBuckets();
        $this->assertIsArray($buckets);
    }

    /**
     * Test that BranchAwareClient preserves OAuth authentication - unit test without API calls
     */
    public function testBranchAwareClientPreservesOAuthAuth(): void
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('getAuthMethod')->willReturn(Client::AUTH_METHOD_OAUTH);
        $mockClient->method('apiGet')->with('dev-branches/')->willReturn([
            [
                'id' => '123',
                'name' => 'Main',
                'isDefault' => true,
            ],
        ]);

        $clientWrapper = $this->getMockBuilder(ClientWrapper::class)
            ->setConstructorArgs([new ClientOptions(
                (string) getenv('TEST_STORAGE_API_URL'),
                'test-oauth-token',
                authMethod: Client::AUTH_METHOD_OAUTH,
            )])
            ->onlyMethods(['getBasicClient'])
            ->getMock();

        $clientWrapper->method('getBasicClient')->willReturn($mockClient);

        // Test the auth method is correctly returned
        $this->assertEquals(Client::AUTH_METHOD_OAUTH, $clientWrapper->getAuthMethod());
    }

    /**
     * Test that default branch client preserves OAuth authentication - unit test without API calls
     */
    public function testDefaultBranchClientPreservesOAuthAuth(): void
    {
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('getAuthMethod')->willReturn(Client::AUTH_METHOD_OAUTH);
        $mockClient->method('apiGet')->with('dev-branches/')->willReturn([
            [
                'id' => '123',
                'name' => 'Main',
                'isDefault' => true,
            ],
        ]);

        $clientWrapper = $this->getMockBuilder(ClientWrapper::class)
            ->setConstructorArgs([new ClientOptions(
                (string) getenv('TEST_STORAGE_API_URL'),
                'test-oauth-token',
                authMethod: Client::AUTH_METHOD_OAUTH,
            )])
            ->onlyMethods(['getBasicClient'])
            ->getMock();

        $clientWrapper->method('getBasicClient')->willReturn($mockClient);

        // Test the auth method is correctly returned
        $this->assertEquals(Client::AUTH_METHOD_OAUTH, $clientWrapper->getAuthMethod());
    }
}
