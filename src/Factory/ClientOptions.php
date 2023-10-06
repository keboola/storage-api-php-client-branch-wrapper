<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Factory;

use Closure;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\Options\BackendConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

class ClientOptions
{
    private ?string $url;
    private ?string $token;
    private ?string $branchId;
    private ?string $runId;
    private ?LoggerInterface $logger;
    private ?string $userAgent;
    private ?int $backoffMaxTries;
    private ?int $awsRetries;
    private ?bool $awsDebug;
    private ?Closure $jobPollRetryDelay;
    private ?Closure $runIdGenerator;
    private ?BackendConfiguration $backendConfiguration;
    private ?bool $useBranchStorage;

    public function getClientConstructOptions(): array
    {
        return [
            'url' => $this->getUrl(),
            'userAgent' => $this->getUserAgent(),
            'token' => $this->getToken(),
            'backoffMaxTries' => $this->getBackoffMaxTries(),
            'awsRetries' => $this->getAwsRetries(),
            'awsDebug' => $this->getAwsDebug(),
            'logger' => $this->getLogger(),
            'jobPollRetryDelay' => $this->getJobPollRetryDelay(),
        ];
    }

    public function __construct(
        ?string $url = null,
        ?string $token = null,
        ?string $branchId = null,
        ?string $runId = null,
        ?LoggerInterface $logger = null,
        ?string $userAgent = null,
        ?int $backoffMaxTries = null,
        ?int $awsRetries = null,
        ?bool $awsDebug = null,
        ?Closure $jobPollRetryDelay = null,
        ?Closure $runIdGenerator = null,
        ?BackendConfiguration $backendConfiguration = null,
        ?bool $useBranchStorage = null,
    ) {
        $this->setUrl($url);
        $this->setToken($token);
        $this->setBranchId($branchId);
        $this->setRunId($runId);
        $this->setLogger($logger);
        $this->setUserAgent($userAgent);
        $this->setBackoffMaxTries($backoffMaxTries);
        $this->setAwsRetries($awsRetries);
        $this->setAwsDebug($awsDebug);
        $this->setJobPollRetryDelay($jobPollRetryDelay);
        $this->setRunIdGenerator($runIdGenerator);
        $this->setBackendConfiguration($backendConfiguration);
        $this->setUseBranchStorage($useBranchStorage);
    }

    public function addValuesFrom(ClientOptions $clientOptions): void
    {
        $this->url = $clientOptions->getUrl() ?? $this->url;
        $this->token = $clientOptions->getToken() ?? $this->token;
        $this->branchId = $clientOptions->getBranchId() ?? $this->branchId;
        $this->runId = $clientOptions->getRunId() ?? $this->runId;
        $this->logger = $clientOptions->getLogger() ?? $this->logger;
        $this->userAgent = $clientOptions->getUserAgent() ?? $this->userAgent;
        $this->backoffMaxTries = $clientOptions->getBackoffMaxTries() ?? $this->backoffMaxTries;
        $this->awsRetries = $clientOptions->getAwsRetries() ?? $this->awsRetries;
        $this->awsDebug = $clientOptions->getAwsDebug() ?? $this->awsDebug;
        $this->jobPollRetryDelay = $clientOptions->getJobPollRetryDelay() ?? $this->jobPollRetryDelay;
        $this->runIdGenerator = $clientOptions->getRunIdGenerator() ?? $this->runIdGenerator;
        $this->backendConfiguration = $clientOptions->getBackendConfiguration() ?? $this->backendConfiguration;
        $this->useBranchStorage = $clientOptions->useBranchStorage() ?? $this->useBranchStorage;
    }

    public function setUrl(?string $url): ClientOptions
    {
        if ($url !== null) {
            $validator = Validation::createValidator();
            $errors = $validator->validate($url, [new Url(['message' => 'Storage API URL is not valid.'])]);
            if ($errors->count() !== 0) {
                throw new ClientException(
                    'Value "' . $errors->get(0)->getInvalidValue() . '" is invalid: ' . $errors->get(0)->getMessage(),
                );
            }
        }
        $this->url = $url;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setToken(?string $token): ClientOptions
    {
        $this->token = $token;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setUserAgent(?string $userAgent): ClientOptions
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setBackoffMaxTries(?int $backoffMaxTries): ClientOptions
    {
        $this->backoffMaxTries = $backoffMaxTries;
        return $this;
    }

    public function getBackoffMaxTries(): ?int
    {
        return $this->backoffMaxTries;
    }

    public function setAwsRetries(?int $awsRetries): ClientOptions
    {
        $this->awsRetries = $awsRetries;
        return $this;
    }

    public function getAwsRetries(): ?int
    {
        return $this->awsRetries;
    }

    public function setAwsDebug(?bool $awsDebug): ClientOptions
    {
        $this->awsDebug = $awsDebug;
        return $this;
    }

    public function getAwsDebug(): ?bool
    {
        return $this->awsDebug;
    }

    public function setLogger(?LoggerInterface $logger): ClientOptions
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function setJobPollRetryDelay(?Closure $jobPollRetryDelay): ClientOptions
    {
        $this->jobPollRetryDelay = $jobPollRetryDelay;
        return $this;
    }

    public function getJobPollRetryDelay(): ?Closure
    {
        return $this->jobPollRetryDelay;
    }

    public function setRunId(?string $runId): ClientOptions
    {
        $this->runId = $runId;
        return $this;
    }

    public function getRunId(): ?string
    {
        return $this->runId;
    }

    public function setBranchId(?string $branchId): ClientOptions
    {
        $this->branchId = $branchId;
        return $this;
    }

    public function getBranchId(): ?string
    {
        return $this->branchId;
    }

    public function setRunIdGenerator(?Closure $runIdGenerator): ClientOptions
    {
        $this->runIdGenerator = $runIdGenerator;
        return $this;
    }

    public function getRunIdGenerator(): ?Closure
    {
        return $this->runIdGenerator;
    }

    public function setBackendConfiguration(?BackendConfiguration $backendConfiguration): ClientOptions
    {
        $this->backendConfiguration = $backendConfiguration;
        return $this;
    }

    public function getBackendConfiguration(): ?BackendConfiguration
    {
        return $this->backendConfiguration;
    }

    public function setUseBranchStorage(?bool $useBranchStorage): ClientOptions
    {
        $this->useBranchStorage = $useBranchStorage;
        return $this;
    }

    public function useBranchStorage(): ?bool
    {
        return $this->useBranchStorage;
    }
}
