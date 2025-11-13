<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Tests;

use Keboola\StorageApiBranch\StorageApiToken;
use PHPUnit\Framework\TestCase;

class StorageApiTokenTest extends TestCase
{
    public function testAccessors(): void
    {
        $token = new StorageApiToken(
            [
                'id' => '123',
                'description' => 'token description',
                'canManageBuckets' => true,
                'canManageTokens' => false,
                'canCreateJobs' => true,
                'owner' => [
                    'id' => '456',
                    'name' => 'my project',
                    'features' => ['foo', 'bar'],
                    'payAsYouGo' => [
                        'purchasedCredits' => 1.23,
                    ],
                    'fileStorageProvider' => 'aws',
                    'defaultBackend' => 'snowflake',
                    'isBYODB' => true,
                ],
                'admin' => [
                    'samlParameters' => [
                        'userId' => '789',
                    ],
                    'role' => 'admin',
                    'id' => '246',
                ],
                'componentAccess' => ['keboola.component'],
            ],
            'tokenValue',
        );

        self::assertSame('123', $token->getTokenId());
        self::assertSame('tokenValue', $token->getTokenValue());
        self::assertSame('456', $token->getProjectId());
        self::assertSame(['foo', 'bar'], $token->getFeatures());
        self::assertTrue($token->hasFeature('foo'));
        self::assertFalse($token->hasFeature('baz'));
        self::assertSame(1.23, $token->getPayAsYouGoPurchasedCredits());
        self::assertSame('789', $token->getSamlUserId());
        self::assertSame('aws', $token->getFileStorageProvider());
        self::assertSame('my project', $token->getProjectName());
        self::assertSame('token description', $token->getTokenDesc());
        self::assertSame('admin', $token->getRole());
        self::assertSame('246', $token->getUserId());
        self::assertSame(['admin'], $token->getRoles());
        self::assertSame(['keboola.component'], $token->getAllowedComponents());
        self::assertSame(['canManageBuckets', 'canCreateJobs'], $token->getPermissions());
        self::assertTrue($token->isAdminToken());
        self::assertSame('snowflake', $token->getProjectBackend());
        self::assertTrue($token->isBYODB());

        self::assertSame(
            [
                'id' => '123',
                'description' => 'token description',
                'canManageBuckets' => true,
                'canManageTokens' => false,
                'canCreateJobs' => true,
                'owner' => [
                    'id' => '456',
                    'name' => 'my project',
                    'features' => ['foo', 'bar'],
                    'payAsYouGo' => [
                        'purchasedCredits' => 1.23,
                    ],
                    'fileStorageProvider' => 'aws',
                    'defaultBackend' => 'snowflake',
                    'isBYODB' => true,
                ],
                'admin' => [
                    'samlParameters' => [
                        'userId' => '789',
                    ],
                    'role' => 'admin',
                    'id' => '246',
                ],
                'componentAccess' => ['keboola.component'],
            ],
            $token->getTokenInfo(),
        );

        $token = new StorageApiToken([], 'tokenValue');
        self::assertFalse($token->isAdminToken());
    }

    public function testAccessorsTypeZealous(): void
    {
        $token = new StorageApiToken(
            [
                'id' => 123,
                'description' => 'token description',
                'canCreateJobs' => true,
                'canManageBuckets' => true,
                'youcanttouchthis' => true,
                'canManageBuckets"' => true,
                'owner' => [
                    'id' => 456,
                    'name' => 'my project',
                    'payAsYouGo' => [
                        'purchasedCredits' => 1,
                    ],
                    'defaultBackend' => 'snowflake',
                ],
                'admin' => [
                    'samlParameters' => [
                        'userId' => 789,
                    ],
                    'id' => '246',
                ],
            ],
            'tokenValue',
        );

        self::assertSame(['canCreateJobs', 'canManageBuckets'], $token->getPermissions());
        self::assertSame('123', $token->getTokenId());
        self::assertSame('456', $token->getProjectId());
        self::assertSame(1.0, $token->getPayAsYouGoPurchasedCredits());
        self::assertSame('789', $token->getSamlUserId());
    }

    public function testPurchasedCreditsMissing(): void
    {
        $token = new StorageApiToken(
            [
                'id' => 123,
                'description' => 'token description',
                'owner' => [
                    'id' => 456,
                    'name' => 'my project',
                ],
            ],
            'tokenValue',
        );

        self::assertSame(0.0, $token->getPayAsYouGoPurchasedCredits());
    }

    public function testNoRoles(): void
    {
        $token = new StorageApiToken(
            [
                'id' => '123',
                'description' => 'token description',
                'owner' => [
                    'id' => '456',
                    'name' => 'my project',
                    'features' => ['foo', 'bar'],
                    'payAsYouGo' => [
                        'purchasedCredits' => 1.23,
                    ],
                    'fileStorageProvider' => 'aws',
                    'defaultBackend' => 'snowflake',
                ],
                'admin' => [
                    'samlParameters' => [
                        'userId' => '789',
                    ],
                    'id' => '246',
                ],
            ],
            'tokenValue',
        );

        self::assertNull($token->getRole());
        self::assertSame([], $token->getRoles());
    }

    public function testNoComponentAccessLimits(): void
    {
        $token = new StorageApiToken(
            [
                'id' => '123',
                'owner' => [
                    'id' => '456',
                    'name' => 'my project',
                    'features' => [],
                ],
                'componentAccess' => [],
            ],
            'tokenValue',
        );

        self::assertSame([], $token->getAllowedComponents());
    }

    public function testAllComponentAccessLimits(): void
    {
        $token = new StorageApiToken(
            [
                'id' => '123',
                'owner' => [
                    'id' => '456',
                    'name' => 'my project',
                    'features' => [],
                ],
            ],
            'tokenValue',
        );

        self::assertSame(null, $token->getAllowedComponents());
    }

    public function testSamlNotSet(): void
    {
        $token = new StorageApiToken(
            [
                'id' => '123',
                'description' => 'token description',
                'owner' => [
                    'id' => '456',
                    'name' => 'my project',
                    'features' => ['foo', 'bar'],
                    'payAsYouGo' => [
                        'purchasedCredits' => 1.23,
                    ],
                    'fileStorageProvider' => 'aws',
                    'defaultBackend' => 'snowflake',
                ],
                'admin' => [
                    'id' => '246',
                ],
            ],
            'tokenValue',
        );

        self::assertNull($token->getSamlUserId());
    }
}
