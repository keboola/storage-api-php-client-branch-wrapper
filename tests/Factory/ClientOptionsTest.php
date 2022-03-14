<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Tests\Factory;

use Keboola\StorageApi\ClientException;
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
            $runIdGenerator
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
    }

    public function testAccessors(): void
    {
        $retryFunction = function () {
        };
        $runIdFunction = function () {
        };
        $logger = new NullLogger();
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
    }

    public function testSetInvalidUrl(): void
    {
        $clientOptions = new ClientOptions();
        $this->expectExceptionMessage('r');
        $this->expectException(ClientException::class);
        $clientOptions->setUrl('boo');
    }

    public function testInvalidUrlConstruct(): void
    {
        $this->expectExceptionMessage('r');
        $this->expectException(ClientException::class);
        new ClientOptions('boo');
    }

    public function testAddValuesFrom(): void
    {
        $retryFunction = function () {
        };
        $runIdFunction = function () {
        };
        $logger = new NullLogger();
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
            $runIdFunction
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
            $retryFunction
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
        self::assertSame($retryFunction, $clientOptions1->getJobPollRetryDelay());
        self::assertSame($runIdFunction, $clientOptions1->getRunIdGenerator());

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
            null
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
        self::assertSame($retryFunction, $clientOptions1->getJobPollRetryDelay());
        self::assertSame($runIdFunction, $clientOptions1->getRunIdGenerator());
    }

    public function testGetClientConstructOptions(): void
    {
        $retryFunction = function () {
        };
        $logger = new NullLogger();
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
            $retryFunction
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
            $clientOptions->getClientConstructOptions()
        );
    }
}
