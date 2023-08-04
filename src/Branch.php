<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch;

use Keboola\StorageApi\BranchAwareClient;

class Branch
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly bool $isDefault,
        public ?BranchAwareClient $client = null,
    ) {
    }
}
