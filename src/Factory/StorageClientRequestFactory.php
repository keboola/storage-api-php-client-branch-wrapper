<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Factory;

use Keboola\StorageApi\Client;
use Keboola\StorageApi\ClientException;
use Keboola\StorageApiBranch\ClientWrapper;
use Symfony\Component\HttpFoundation\Request;

class StorageClientRequestFactory implements StorageClientFactoryInterface
{
    public const TOKEN_HEADER = 'X-StorageApi-Token';
    public const AUTHORIZATION_HEADER = 'Authorization';
    public const BEARER_PREFIX = 'Bearer ';
    public const RUN_ID_HEADER = 'X-KBC-RunId';

    private ClientOptions $clientOptions;

    public function __construct(ClientOptions $clientOptions)
    {
        $this->clientOptions = new ClientOptions();
        $this->clientOptions->addValuesFrom($clientOptions);
    }

    /**
     * @return array{token: string, authType: string|null}
     */
    private function getTokenFromRequest(Request $request): array
    {
        // Check for Bearer token first (takes precedence)
        $authHeader = (string) $request->headers->get(self::AUTHORIZATION_HEADER);
        if (str_starts_with($authHeader, self::BEARER_PREFIX)) {
            return [
                'token' => substr($authHeader, strlen(self::BEARER_PREFIX)),
                'authType' => Client::AUTH_TYPE_BEARER,
            ];
        }

        // Fall back to X-StorageApi-Token header
        $token = (string) $request->headers->get(self::TOKEN_HEADER);
        if ($token === '') {
            throw new ClientException(
                sprintf(
                    'Storage API token must be supplied in "%s" or "%s: Bearer <token>" header.',
                    self::TOKEN_HEADER,
                    self::AUTHORIZATION_HEADER,
                ),
                401,
            );
        }

        return [
            'token' => $token,
            'authType' => null,
        ];
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

        $tokenInfo = $this->getTokenFromRequest($request);
        $options->setToken($tokenInfo['token']);
        $options->setAuthType($tokenInfo['authType']);
        $options->setRunId($this->getRunId($request, $options));

        return new ClientWrapper($options);
    }

    public function getClientOptionsReadOnly(): ClientOptions
    {
        return clone $this->clientOptions;
    }
}
