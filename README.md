# Storage API PHP Client Branch Wrapper [![Build Status](https://dev.azure.com/keboola-dev/storage-api-php-client-branch-wrapper/_apis/build/status/keboola.storage-api-php-client-branch-wrapper?branchName=main)](https://dev.azure.com/keboola-dev/storage-api-php-client-branch-wrapper/_build/latest?definitionId=52&branchName=main)

This is a wrapper for encapsulating Client and BranchAwareClient of [Storage API PHP Client](https://github.com/keboola/storage-api-php-client).

## Installation

    composer require keboola/storage-api-php-client-branch-wrapper
    
## Usage

Create client instance and use it in wrapper instance:

```php 
$client = ClientOptions('http://xxx.com', '1234-XXXX', '123');
$clientWrapper = new ClientWrapper($client);
$branchClient = $clietnWrapper->getBranchClient();
```
## Development

Create a test Keboola Connection project and set `TEST_STORAGE_API_URL` and `TEST_STORAGE_API_TOKEN` environment variables. Use the `.env.dist`
file to create `.env` file.

Run tests with:

    docker-compose run --rm tests74
