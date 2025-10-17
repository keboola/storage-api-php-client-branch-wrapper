<?php

declare(strict_types=1);

use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotEnv = new Dotenv();
$dotEnv->usePutenv();
$dotEnv->bootEnv(dirname(__DIR__).'/.env', 'dev', []);

$requiredEnvs = ['TEST_STORAGE_API_URL', 'TEST_STORAGE_API_TOKEN'];
foreach ($requiredEnvs as $env) {
    if (empty(getenv($env))) {
        throw new Exception(sprintf('The "%s" environment variable is empty.', $env));
    }
}

$client = new Client(
    [
        'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
        'url' => (string) getenv('TEST_STORAGE_API_URL'),
    ],
);

try {
    $tokenInfo = $client->verifyToken();
    print(sprintf(
        'Authorized as "%s (%s)" to project "%s (%s)" at "%s" stack.' . PHP_EOL,
        $tokenInfo['description'],
        $tokenInfo['id'],
        $tokenInfo['owner']['name'],
        $tokenInfo['owner']['id'],
        $client->getApiUrl(),
    ));
} catch (ClientException $e) {
    throw new RuntimeException(
        sprintf('Failed to verify TEST_STORAGE_API_TOKEN "%s".', $e->getMessage()),
        0,
        $e,
    );
}
