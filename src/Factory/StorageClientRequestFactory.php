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

    private ClientOptions $clientOptions;

    public function __construct(ClientOptions $clientOptions)
    {
        $this->clientOptions = new ClientOptions();
        $this->clientOptions->addValuesFrom($clientOptions);
    }

    private function getTokenFromRequest(Request $request): string
    {
        $token = (string) $request->headers->get(self::TOKEN_HEADER);

        if ($token === '') {
            throw new ClientException(
                sprintf('Storage API token must be supplied in %s header.', self::TOKEN_HEADER),
                401
            );
        }

        return $token;
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

    public function createClientWrapper(Request $request): ClientWrapper
    {
        $options = clone $this->clientOptions;
        $options->setToken($this->getTokenFromRequest($request));
        $options->setRunId($this->getRunId($request, $options));
        return new ClientWrapper($options);
    }

    public function getClientOptionsReadOnly(): ClientOptions
    {
        return clone $this->clientOptions;
    }
}
