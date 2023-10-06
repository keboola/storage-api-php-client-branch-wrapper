<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Tests\Factory;

use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\Options\BackendConfiguration;
use Keboola\StorageApiBranch\Factory\ClientOptions;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ClientOptionsTest extends TestCase
{
    public function testConstructor(): void
    {
        $retryFunction = function () {
        };
        $runIdGenerator = function (ClientOptions $clientOptions) {
            return 'boo' . $clientOptions->getToken();
        };
        $logger = new NullLogger();
        $backendConfiguration = new BackendConfiguration('123-transformation', 'small');
        $clientOptions = new ClientOptions(
            'http://dummy',
            'token',
            'branchId',
            'runId',
            $logger,
            'ua',
            42,
            24,
            false,
            $retryFunction,
            $runIdGenerator,
            $backendConfiguration,
            true,
        );

        self::assertSame('http://dummy', $clientOptions->getUrl());
        self::assertSame('token', $clientOptions->getToken());
        self::assertSame('branchId', $clientOptions->getBranchId());
        self::assertSame('runId', $clientOptions->getRunId());
        self::assertSame($logger, $clientOptions->getLogger());
        self::assertSame('ua', $clientOptions->getUserAgent());
        self::assertSame(42, $clientOptions->getBackoffMaxTries());
        self::assertSame(24, $clientOptions->getAwsRetries());
        self::assertSame(false, $clientOptions->getAwsDebug());
        self::assertSame($retryFunction, $clientOptions->getJobPollRetryDelay());
        self::assertSame($runIdGenerator, $clientOptions->getRunIdGenerator());
        self::assertSame('bootoken', $clientOptions->getRunIdGenerator()($clientOptions));
        self::assertSame($backendConfiguration, $clientOptions->getBackendConfiguration());
        self::assertSame(true, $clientOptions->useBranchStorage());
    }

    public function testAccessors(): void
    {
        $retryFunction = function () {
        };
        $runIdFunction = function () {
        };
        $logger = new NullLogger();
        $backendConfiguration = new BackendConfiguration('123-transformation', 'small');
        $clientOptions = new ClientOptions();

        self::assertSame(null, $clientOptions->getUrl());
        self::assertSame(null, $clientOptions->getToken());
        self::assertSame(null, $clientOptions->getBranchId());
        self::assertSame(null, $clientOptions->getRunId());
        self::assertSame(null, $clientOptions->getLogger());
        self::assertSame(null, $clientOptions->getUserAgent());
        self::assertSame(null, $clientOptions->getBackoffMaxTries());
        self::assertSame(null, $clientOptions->getAwsRetries());
        self::assertSame(null, $clientOptions->getAwsDebug());
        self::assertSame(null, $clientOptions->getJobPollRetryDelay());
        self::assertSame(null, $clientOptions->getBackendConfiguration());
        self::assertSame(null, $clientOptions->useBranchStorage());

        $clientOptions->setUrl('http://dummy');
        $clientOptions->setToken('token');
        $clientOptions->setBranchId('branchId');
        $clientOptions->setRunId('runId');
        $clientOptions->setLogger($logger);
        $clientOptions->setUserAgent('ua');
        $clientOptions->setBackoffMaxTries(42);
        $clientOptions->setAwsRetries(24);
        $clientOptions->setAwsDebug(false);
        $clientOptions->setJobPollRetryDelay($retryFunction);
        $clientOptions->setRunIdGenerator($runIdFunction);
        $clientOptions->setBackendConfiguration($backendConfiguration);
        $clientOptions->setUseBranchStorage(true);

        self::assertSame('http://dummy', $clientOptions->getUrl());
        self::assertSame('token', $clientOptions->getToken());
        self::assertSame('branchId', $clientOptions->getBranchId());
        self::assertSame('runId', $clientOptions->getRunId());
        self::assertEquals($logger, $clientOptions->getLogger());
        self::assertSame('ua', $clientOptions->getUserAgent());
        self::assertSame(42, $clientOptions->getBackoffMaxTries());
        self::assertSame(24, $clientOptions->getAwsRetries());
        self::assertSame(false, $clientOptions->getAwsDebug());
        self::assertEquals($retryFunction, $clientOptions->getJobPollRetryDelay());
        self::assertSame($runIdFunction, $clientOptions->getRunIdGenerator());
        self::assertSame($backendConfiguration, $clientOptions->getBackendConfiguration());
        self::assertSame(true, $clientOptions->useBranchStorage());
    }

    public function testSetInvalidUrl(): void
    {
        $clientOptions = new ClientOptions();
        $this->expectExceptionMessage('Value "boo" is invalid: Storage API URL is not valid.');
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(0);
        $clientOptions->setUrl('boo');
    }

    public function testInvalidUrlConstruct(): void
    {
        $this->expectExceptionMessage('Value "boo" is invalid: Storage API URL is not valid.');
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(0);
        new ClientOptions('boo');
    }

    public function testAddValuesFrom(): void
    {
        $retryFunction = function () {
        };
        $runIdFunction = function () {
        };
        $backendConfiguration = new BackendConfiguration('123-transformation', 'small');
        $logger = new NullLogger();
        $retryFunction2 = function () {
        };
        $runIdFunction2 = function () {
        };
        $backendConfiguration2 = new BackendConfiguration('123-transformation', 'small');
        $clientOptions1 = new ClientOptions(
            'http://dummy',
            'token',
            'branchId',
            'runId',
            $logger,
            'ua',
            42,
            24,
            false,
            $retryFunction,
            $runIdFunction,
            $backendConfiguration,
            false,
        );
        $clientOptions2 = new ClientOptions(
            'http://dummy2',
            'token2',
            'branchId2',
            'runId2',
            $logger,
            'ua2',
            422,
            242,
            true,
            $retryFunction2,
            $runIdFunction2,
            $backendConfiguration2,
            true,
        );
        $clientOptions1->addValuesFrom($clientOptions2);
        self::assertSame('http://dummy2', $clientOptions1->getUrl());
        self::assertSame('token2', $clientOptions1->getToken());
        self::assertSame('branchId2', $clientOptions1->getBranchId());
        self::assertSame('runId2', $clientOptions1->getRunId());
        self::assertSame($logger, $clientOptions1->getLogger());
        self::assertSame('ua2', $clientOptions1->getUserAgent());
        self::assertSame(422, $clientOptions1->getBackoffMaxTries());
        self::assertSame(242, $clientOptions1->getAwsRetries());
        self::assertSame(true, $clientOptions1->getAwsDebug());
        self::assertSame($retryFunction2, $clientOptions1->getJobPollRetryDelay());
        self::assertSame($runIdFunction2, $clientOptions1->getRunIdGenerator());
        self::assertSame($backendConfiguration2, $clientOptions1->getBackendConfiguration());
        self::assertSame(true, $clientOptions1->useBranchStorage());

        $clientOptions3 = new ClientOptions(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );
        $clientOptions1->addValuesFrom($clientOptions3);
        self::assertSame('http://dummy2', $clientOptions1->getUrl());
        self::assertSame('token2', $clientOptions1->getToken());
        self::assertSame('branchId2', $clientOptions1->getBranchId());
        self::assertSame('runId2', $clientOptions1->getRunId());
        self::assertSame($logger, $clientOptions1->getLogger());
        self::assertSame('ua2', $clientOptions1->getUserAgent());
        self::assertSame(422, $clientOptions1->getBackoffMaxTries());
        self::assertSame(242, $clientOptions1->getAwsRetries());
        self::assertSame(true, $clientOptions1->getAwsDebug());
        self::assertSame($retryFunction2, $clientOptions1->getJobPollRetryDelay());
        self::assertSame($runIdFunction2, $clientOptions1->getRunIdGenerator());
        self::assertSame($backendConfiguration2, $clientOptions1->getBackendConfiguration());
        self::assertSame(true, $clientOptions1->useBranchStorage());
    }

    public function testGetClientConstructOptions(): void
    {
        $retryFunction = function () {
        };
        $runIdFunction = function () {
        };
        $logger = new NullLogger();
        $backendConfiguration = new BackendConfiguration('123-transformation', 'small');
        $clientOptions = new ClientOptions(
            'http://dummy',
            'token',
            'branchId',
            'runId',
            $logger,
            'ua',
            42,
            24,
            false,
            $retryFunction,
            $runIdFunction,
            $backendConfiguration,
            false,
        );
        self::assertEquals(
            [
                'url' => 'http://dummy',
                'userAgent' => 'ua',
                'token' => 'token',
                'backoffMaxTries' => 42,
                'awsRetries' => 24,
                'awsDebug' => false,
                'logger' => $logger,
                'jobPollRetryDelay' => $retryFunction,
            ],
            $clientOptions->getClientConstructOptions(),
        );
    }
}
