<?php

declare(strict_types=1);

namespace App\Messenger\Transport\AutoMapper\Conversation;

use App\General\Transport\AutoMapper\RestRequestMapper;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\User;

use function array_map;
use function filter_var;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOL;

/**
 * @package App\Messenger
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, non-empty-string>
     */
    protected static array $properties = [
        'title',
        'isGroup',
        'participants',
    ];

    public function __construct(
        private readonly UserResource $userResource,
    ) {
    }

    /**
     * @param array<int, string> $participants
     *
     * @return array<int, User>
     */
    protected function transformParticipants(array $participants): array
    {
        return array_map(
            fn (string $userId): User => $this->userResource->getReference($userId),
            $participants,
        );
    }

    protected function transformIsGroup(bool|string|null $isGroup): bool
    {
        $filtered = filter_var($isGroup, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $filtered ?? (bool)$isGroup;
    }
}
