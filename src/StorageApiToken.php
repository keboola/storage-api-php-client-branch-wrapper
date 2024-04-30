<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch;

use SensitiveParameter;

class StorageApiToken
{
    public function __construct(
        private readonly array $tokenInfo,
        #[SensitiveParameter] private readonly string $tokenValue,
    ) {
    }

    public function getTokenInfo(): array
    {
        return $this->tokenInfo;
    }

    public function getTokenValue(): string
    {
        return $this->tokenValue;
    }

    public function getProjectId(): string
    {
        return (string) $this->tokenInfo['owner']['id'];
    }

    public function getTokenId(): string
    {
        return (string) $this->tokenInfo['id'];
    }

    /**
     * @return list<string>
     */
    public function getFeatures(): array
    {
        return $this->tokenInfo['owner']['features'];
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->getFeatures(), true);
    }

    public function getPayAsYouGoPurchasedCredits(): float
    {
        return (float) ($this->tokenInfo['owner']['payAsYouGo']['purchasedCredits'] ?? 0.0);
    }

    public function getSamlUserId(): ?string
    {
        return $this->tokenInfo['admin']['samlParameters']['userId'] ?? null;
    }

    public function getFileStorageProvider(): string
    {
        return $this->tokenInfo['owner']['fileStorageProvider'];
    }

    public function getProjectName(): string
    {
        return $this->tokenInfo['owner']['name'];
    }

    public function getTokenDesc(): string
    {
        return $this->tokenInfo['description'];
    }

    public function getRole(): ?string
    {
        return $this->tokenInfo['admin']['role'] ?? null;
    }

    public function getRoles(): array
    {
        return !empty($this->tokenInfo['admin']['role']) ? [$this->tokenInfo['admin']['role']] : [];
    }

    public function getAllowedComponents(): ?array
    {
        return $this->tokenInfo['componentAccess'] ?? null;
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return array_filter(
            array_keys(
                array_filter($this->tokenInfo, function (mixed $value): bool {
                    return $value === true;
                }),
            ),
            function (string $value): bool {
                return (bool) preg_match('/^can[a-z]+$/ui', $value);
            },
        );
    }

    public function isAdminToken(): bool
    {
        return !empty($this->tokenInfo['admin']);
    }
}
