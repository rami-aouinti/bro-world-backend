<?php

declare(strict_types=1);

namespace App\Workplace\Transport\AutoMapper\Workplace;

use App\General\Transport\AutoMapper\RestRequestMapper;
use App\User\Application\Resource\PluginResource;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\Plugin;
use App\User\Domain\Entity\User;

use function array_filter;
use function array_map;
use function filter_var;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOL;

/**
 * @package App\User
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, non-empty-string>
     */
    protected static array $properties = [
        'name',
        'isPrivate',
        'enabled',
        'plugins',
        'members',
    ];

    public function __construct(
        private readonly UserResource $userResource,
        private readonly PluginResource $pluginResource,
    ) {
    }

    /**
     * @param array<int, string>|string|null $plugins
     *
     * @return array<int, Plugin>
     */
    protected function transformPlugins(array|string|null $plugins): array
    {
        $pluginIds = $this->normalizeIdentifiers($plugins);

        return array_map(
            fn (string $pluginId): Plugin => $this->pluginResource->getReference($pluginId),
            $pluginIds,
        );
    }

    /**
     * @param array<int, string>|string|null $members
     *
     * @return array<int, User>
     */
    protected function transformMembers(array|string|null $members): array
    {
        $memberIds = $this->normalizeIdentifiers($members);

        return array_map(
            fn (string $memberId): User => $this->userResource->getReference($memberId),
            $memberIds,
        );
    }

    protected function transformIsPrivate(bool|int|string|null $isPrivate): bool
    {
        $filtered = filter_var($isPrivate, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $filtered ?? (bool)$isPrivate;
    }

    protected function transformEnabled(bool|int|string|null $enabled): bool
    {
        $filtered = filter_var($enabled, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $filtered ?? (bool)$enabled;
    }

    /**
     * @param array<int, string>|string|null $identifiers
     *
     * @return array<int, string>
     */
    private function normalizeIdentifiers(array|string|null $identifiers): array
    {
        if ($identifiers === null) {
            return [];
        }

        $ids = is_array($identifiers) ? $identifiers : [$identifiers];

        return array_filter(
            array_map(static fn ($value): ?string => $value !== '' ? (string)$value : null, $ids),
        );
    }
}
