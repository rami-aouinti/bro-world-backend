<?php

declare(strict_types=1);

namespace App\Messenger\Domain\Repository\Interfaces;

use App\Messenger\Domain\Entity\Conversation;
use App\User\Domain\Entity\User;

/**
 * @package App\Messenger
 */
interface ConversationRepositoryInterface
{
    /**
     * @return array<int, Conversation>
     */
    public function findByParticipantId(User $user): array;
}
