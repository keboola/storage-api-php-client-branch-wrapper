<?php

declare(strict_types=1);

namespace Keboola\StorageApiBranch;

use Keboola\StorageApiBranch\Factory\AuthType;
use SensitiveParameter;
use function trigger_deprecation;

class StorageApiToken
{
    private readonly AuthType $tokenType;

    public function __construct(
        private readonly array $tokenInfo,
        #[SensitiveParameter] private readonly string $tokenValue,
        ?AuthType $tokenType = null,
    ) {
        if ($tokenType === null) {
            trigger_deprecation(
                'keboola/storage-api-php-client-branch-wrapper',
                '6.8',
                'Constructing %s without the $tokenType argument is deprecated; it will be required in 7.0. '
                . 'Pass AuthType::STORAGE_TOKEN explicitly for legacy Storage tokens.',
                self::class,
            );
            $tokenType = AuthType::STORAGE_TOKEN;
        }

        $this->tokenType = $tokenType;
    }

    /**
     * Auth scheme the token authenticates with: {@see AuthType::STORAGE_TOKEN} (sent as the
     * X-StorageApi-Token header) or {@see AuthType::BEARER} (sent as an Authorization: Bearer
     * header, e.g. OAuth tokens). Lets callers build a Storage client with the matching scheme.
     */
    public function getTokenType(): AuthType
    {
        return $this->tokenType;
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
        $userId = $this->tokenInfo['admin']['samlParameters']['userId'] ?? null;
        return ($userId !== null) ? (string) $userId : null;
    }

    public function getUserId(): string
    {
        return (string) $this->tokenInfo['admin']['id'];
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

    public function getProjectBackend(): string
    {
        return $this->tokenInfo['owner']['defaultBackend'];
    }

    public function isBYODB(): bool
    {
        return (bool) $this->tokenInfo['owner']['isBYODB'];
    }
}
