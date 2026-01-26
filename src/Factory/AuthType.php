<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch\Factory;

use Keboola\StorageApi\Client;

enum AuthType: string
{
    case STORAGE_TOKEN = Client::AUTH_TYPE_STORAGE_TOKEN;
    case BEARER = Client::AUTH_TYPE_BEARER;
}
