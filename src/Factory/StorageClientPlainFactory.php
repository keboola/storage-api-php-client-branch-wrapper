<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Factory;

use Keboola\StorageApiBranch\ClientWrapper;

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
}
