<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Factory;

interface StorageClientFactoryInterface
{
    public function getClientOptionsReadOnly(): ClientOptions;
}
