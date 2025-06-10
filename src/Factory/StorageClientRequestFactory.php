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
    public const RUN_ID_HEADER = 'X-KBC-RunId';

    private ClientOptions $clientOptions;

    public function __construct(ClientOptions $clientOptions)
    {
        $this->clientOptions = new ClientOptions();
        $this->clientOptions->addValuesFrom($clientOptions);
    }

    private function getTokenAndAuthMethodFromRequest(Request $request): array
    {
        $token = (string) $request->headers->get(self::TOKEN_HEADER);
        $authorization = (string) $request->headers->get(self::AUTHORIZATION_HEADER);

        // Check for OAuth token first (Authorization: Bearer token)
        if ($authorization !== '' && str_starts_with($authorization, 'Bearer ')) {
            $oauthToken = substr($authorization, 7); // Remove "Bearer " prefix
            if ($oauthToken === '') {
                throw new ClientException(
                    'OAuth token must be provided in Authorization header with Bearer prefix.',
                    401,
                );
            }
            return [$oauthToken, Client::AUTH_METHOD_OAUTH];
        }

        // Fall back to Storage API token
        if ($token === '') {
            throw new ClientException(
                sprintf(
                    'Storage API token must be supplied in %s header or OAuth token in %s header with Bearer prefix.',
                    self::TOKEN_HEADER,
                    self::AUTHORIZATION_HEADER,
                ),
                401,
            );
        }

        return [$token, Client::AUTH_METHOD_TOKEN];
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

        [$token, $authMethod] = $this->getTokenAndAuthMethodFromRequest($request);
        $options->setToken($token);
        $options->setAuthMethod($authMethod);
        $options->setRunId($this->getRunId($request, $options));

        return new ClientWrapper($options);
    }

    public function getClientOptionsReadOnly(): ClientOptions
    {
        return clone $this->clientOptions;
    }
}
