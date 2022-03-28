<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Tests\Factory;

use Keboola\StorageApiBranch\Factory\ClientOptions;
use Keboola\StorageApiBranch\Factory\StorageClientPlainFactory;
use PHPUnit\Framework\TestCase;

class StorageClientPlainFactoryTest extends TestCase
{
    public function testGetClientOptions(): void
    {
        $factory = new StorageClientPlainFactory(new ClientOptions('http://foo'));
        self::assertSame('http://foo', $factory->getClientOptionsReadOnly()->getUrl());
        $factory->getClientOptionsReadOnly()->setUrl('http://bar');
        self::assertSame('http://foo', $factory->getClientOptionsReadOnly()->getUrl());
    }

    public function testFactoryCreatesWorkingClient(): void
    {
        $factory = new StorageClientPlainFactory(new ClientOptions($_SERVER['TEST_STORAGE_API_URL']));
        $clientWrapper = $factory->createClientWrapper(new ClientOptions(null, $_SERVER['TEST_STORAGE_API_TOKEN']));
        $tokenInfo = $clientWrapper->getBasicClient()->verifyToken();

        self::assertArrayHasKey('owner', $tokenInfo);
        self::assertNull($clientWrapper->getClientOptionsReadOnly()->getRunId());
    }

    public function testClientOptionsNotModified(): void
    {
        $options = new ClientOptions($_SERVER['TEST_STORAGE_API_URL']);
        $factory = new StorageClientPlainFactory($options);
        $factory->createClientWrapper(new ClientOptions(null, $_SERVER['TEST_STORAGE_API_TOKEN']));
        self::assertNull($options->getToken());
        self::assertNull($factory->getClientOptionsReadOnly()->getToken());
    }
}
