<?php

declare(strict_types=1);

use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->usePutenv(true)->bootEnv(dirname(__DIR__) . '/.env', 'dev', []);

$requiredEnvs = ['TEST_STORAGE_API_URL', 'TEST_STORAGE_API_TOKEN'];

$client = new Client(
    [
        'token' => (string) getenv('TEST_STORAGE_API_TOKEN'),
        'url' => (string) getenv('TEST_STORAGE_API_URL'),
    ],
);

try {
    $tokenInfo = $client->verifyToken();
} catch (ClientException $e) {
    throw new RuntimeException(
        sprintf('Failed to verify TEST_STORAGE_API_TOKEN "%s".', $e->getMessage()),
        0,
        $e,
    );
}
