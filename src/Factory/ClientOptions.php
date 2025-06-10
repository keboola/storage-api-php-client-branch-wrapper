<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Factory;

use Closure;
use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApi\Options\BackendConfiguration;
use Psr\Log\LoggerInterface;
use SensitiveParameter;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

class ClientOptions
{
    public function __construct(
        private ?string $url = null,
        #[SensitiveParameter] private ?string $token = null,
        private ?string $branchId = null,
        private ?string $runId = null,
        private ?LoggerInterface $logger = null,
        private ?string $userAgent = null,
        private ?int $backoffMaxTries = null,
        private ?int $awsRetries = null,
        private ?bool $awsDebug = null,
        private ?Closure $jobPollRetryDelay = null,
        private ?Closure $runIdGenerator = null,
        private ?BackendConfiguration $backendConfiguration = null,
        private ?bool $useBranchStorage = null,
        private ?bool $retryOnMaintenance = null,
        private ?string $authMethod = null,
    ) {
        $this->setUrl($url); // call to validate URL
        $this->setAuthMethod($authMethod); // call to validate authMethod
    }

    public function getClientConstructOptions(): array
    {
        return [
            'url' => $this->getUrl(),
            'userAgent' => $this->getUserAgent(),
            'token' => $this->getToken(),
            'backoffMaxTries' => $this->getBackoffMaxTries(),
            'retryOnMaintenance' => $this->getRetryOnMaintenance(),
            'awsRetries' => $this->getAwsRetries(),
            'awsDebug' => $this->getAwsDebug(),
            'logger' => $this->getLogger(),
            'jobPollRetryDelay' => $this->getJobPollRetryDelay(),
            'authMethod' => $this->getAuthMethod(),
        ];
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
        $this->retryOnMaintenance = $clientOptions->getRetryOnMaintenance() ?? $this->retryOnMaintenance;
        $this->awsRetries = $clientOptions->getAwsRetries() ?? $this->awsRetries;
        $this->awsDebug = $clientOptions->getAwsDebug() ?? $this->awsDebug;
        $this->jobPollRetryDelay = $clientOptions->getJobPollRetryDelay() ?? $this->jobPollRetryDelay;
        $this->runIdGenerator = $clientOptions->getRunIdGenerator() ?? $this->runIdGenerator;
        $this->backendConfiguration = $clientOptions->getBackendConfiguration() ?? $this->backendConfiguration;
        $this->useBranchStorage = $clientOptions->useBranchStorage() ?? $this->useBranchStorage;
        $this->authMethod = $clientOptions->getAuthMethod() ?? $this->authMethod;
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

    public function setToken(#[SensitiveParameter] ?string $token): ClientOptions
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

    public function getRetryOnMaintenance(): ?bool
    {
        return $this->retryOnMaintenance;
    }

    public function setRetryOnMaintenance(?bool $retryOnMaintenance): void
    {
        $this->retryOnMaintenance = $retryOnMaintenance;
    }

    public function setAuthMethod(?string $authMethod): ClientOptions
    {
        if ($authMethod !== null) {
            $validMethods = [Client::AUTH_METHOD_TOKEN, Client::AUTH_METHOD_OAUTH];
            if (!in_array($authMethod, $validMethods)) {
                throw new ClientException(
                    sprintf(
                        'authMethod must be "%s" or "%s". "%s" given.',
                        Client::AUTH_METHOD_TOKEN,
                        Client::AUTH_METHOD_OAUTH,
                        $authMethod,
                    ),
                );
            }
        }
        $this->authMethod = $authMethod;
        return $this;
    }

    public function getAuthMethod(): ?string
    {
        return $this->authMethod;
    }
}
