<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Factory;

use Keboola\StorageApi\ClientException;
use Keboola\StorageApiBranch\ClientWrapper;
use Symfony\Component\HttpFoundation\Request;

class StorageClientRequestFactory implements StorageClientFactoryInterface
{
    public const TOKEN_HEADER = 'X-StorageApi-Token';
    public const RUN_ID_HEADER = 'X-KBC-RunId';
    private const AUTHORIZATION_HEADER = 'Authorization';
    private const BEARER_PREFIX = 'Bearer ';

    private ClientOptions $clientOptions;

    public function __construct(ClientOptions $clientOptions)
    {
        $this->clientOptions = new ClientOptions();
        $this->clientOptions->addValuesFrom($clientOptions);
    }

    /**
     * @return array{token: string, authType: AuthType}
     */
    private function getTokenFromRequest(Request $request): array
    {
        $authHeader = $request->headers->get(self::AUTHORIZATION_HEADER);
        if ($authHeader !== null && str_starts_with($authHeader, self::BEARER_PREFIX)) {
            return [
                'token' => substr($authHeader, strlen(self::BEARER_PREFIX)),
                'authType' => AuthType::BEARER,
            ];
        }

        $token = (string) $request->headers->get(self::TOKEN_HEADER);
        if ($token !== '') {
            return [
                'token' => $token,
                'authType' => AuthType::STORAGE_TOKEN,
            ];
        }

        throw new ClientException(
            sprintf(
                'Storage API token must be supplied in %s header or %s header.',
                self::AUTHORIZATION_HEADER,
                self::TOKEN_HEADER,
            ),
            401,
        );
    }

    private function getRunId(Request $request, ClientOptions $options): string
    {
        $runId = (string) $request->headers->get(self::RUN_ID_HEADER);

        if ($runId === '') {
            if ($options->getRunIdGenerator() !== null) {
                $runId = $options->getRunIdGenerator()($options);
            } else {
                $runId = uniqid('run-');
            }
        }
        return $runId;
    }

    public function createClientWrapper(Request $request, ?ClientOptions $clientOptions = null): ClientWrapper
    {
        $options = clone $this->clientOptions;
        if ($clientOptions) {
            $options->addValuesFrom($clientOptions);
        }

        $tokenData = $this->getTokenFromRequest($request);
        $options->setToken($tokenData['token']);
        $options->setAuthType($tokenData['authType']);
        $options->setRunId($this->getRunId($request, $options));

        return new ClientWrapper($options);
    }

    public function getClientOptionsReadOnly(): ClientOptions
    {
        return clone $this->clientOptions;
    }
}
