# Storage API PHP Client Branch Wrapper [![Build Status](https://dev.azure.com/keboola-dev/storage-api-php-client-branch-wrapper/_apis/build/status/keboola.storage-api-php-client-branch-wrapper?branchName=main)](https://dev.azure.com/keboola-dev/storage-api-php-client-branch-wrapper/_build/latest?definitionId=52&branchName=main)

This is a wrapper for encapsulating Client and BranchAwareClient of [Storage API PHP Client](https://github.com/keboola/storage-api-php-client).

## Installation

    composer require keboola/storage-api-php-client-branch-wrapper
    
## Usage

Create client instance and use it in wrapper instance:

```php 
$clientOptions = ClientOptions('http://xxx.com', '1234-XXXX', '123');
$clientWrapper = new ClientWrapper($clientOptions);
$branchClient = $clietnWrapper->getBranchClient();
```

Client options refer to the options of the [Storage API Client constructor](https://github.com/keboola/storage-api-php-client/blob/b4cef10b1018d5b4cac06c9d541e790930fa437a/src/Keboola/StorageApi/Client.php#L102).
Except for the `runIdGenerator` option which defines a callback used to generate `runId` when none is provided in 
request (applicable for `StorageClientRequestFactory`).

### OAuth Authentication

The wrapper supports OAuth authentication in addition to the traditional Storage API token authentication:

```php
// OAuth authentication
$clientOptions = new ClientOptions(
    'https://connection.keboola.com',
    'your_oauth_token',
    authMethod: Client::AUTH_METHOD_OAUTH
);
$clientWrapper = new ClientWrapper($clientOptions);
```

OAuth authentication can also be detected automatically via HTTP headers when using `StorageClientRequestFactory`:

```php
// OAuth token via Authorization header (Bearer token)
$request->headers->set('Authorization', 'Bearer your_oauth_token');
$factory = new StorageClientRequestFactory(new ClientOptions($url));
$clientWrapper = $factory->createClientWrapper($request);
```

## Development

Create a test Keboola Connection project and set the following environment variables:
- `TEST_STORAGE_API_URL` - Storage API URL
- `TEST_STORAGE_API_TOKEN` - Storage API token
- `OAUTH_TOKEN` - (optional) OAuth token for testing OAuth authentication. If not provided, OAuth-related tests will be skipped.

Use the `.env.dist` file to create `.env` file.

Run tests with:

    docker-compose run --rm dev

## License

MIT licensed, see [LICENSE](./LICENSE) file.
