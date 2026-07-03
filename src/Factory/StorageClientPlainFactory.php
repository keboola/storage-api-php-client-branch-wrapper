<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Factory;

use Keboola\StorageApiBranch\ClientWrapper;
use Keboola\StorageApiBranch\StorageApiToken;

class StorageClientPlainFactory implements StorageClientFactoryInterface
{
    private ClientOptions $clientOptions;

    public function __construct(ClientOptions $clientOptions)
    {
        $this->clientOptions = $clientOptions;
    }

    public function getClientOptionsReadOnly(): ClientOptions
    {
        return clone $this->clientOptions;
    }

    public function createClientWrapper(ClientOptions $clientOptions): ClientWrapper
    {
        $options = clone $this->clientOptions;
        $options->addValuesFrom($clientOptions);
        return new ClientWrapper($options);
    }

    /**
     * Builds a wrapper authenticating as the given token, honoring its {@see StorageApiToken::getTokenType()}
     * (so an OAuth bearer token is sent with the bearer scheme, a Storage token as X-StorageApi-Token).
     * Extra per-call options (e.g. branchId) are merged over the token-derived ones.
     */
    public function createClientWrapperForToken(
        StorageApiToken $token,
        ?ClientOptions $clientOptions = null,
    ): ClientWrapper {
        $options = new ClientOptions(
            token: $token->getTokenValue(),
            authType: $token->getTokenType(),
        );
        if ($clientOptions !== null) {
            $options->addValuesFrom($clientOptions);
        }

        return $this->createClientWrapper($options);
    }
}
