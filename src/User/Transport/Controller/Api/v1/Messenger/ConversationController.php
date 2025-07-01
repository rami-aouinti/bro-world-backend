<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Messenger;

use App\Messenger\Domain\Entity\Conversation;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class MessengerController
 *
 * @package App\User\Transport\Controller\Api\v1\Messenger
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Messenger')]
readonly class ConversationController
{
    public function __construct(
    )
    {
    }

    /**
     * @param EntityManagerInterface $em
     * @param User                   $loggedInUser
     * @param Conversation           $conversation
     *
     * @throws NotSupported
     * @return JsonResponse
     */
    #[Route('/v1/messenger/conversation/{conversation}', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $em, User $loggedInUser, Conversation $conversation): JsonResponse
    {
        $conversationArray = [
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'isGroup' => $conversation->isGroup(),
            'participants' => $conversation->getParticipants()->map(fn(User $u) => [
                'id' => $u->getId(),
                'username' => $u->getUsername(),
                'firstName' => $u->getFirstName(),
                'lastName' => $u->getLastName(),
                'avatar' => $u->getProfile()?->getPhoto() ?? '/img/person.png']
            )];
        return new JsonResponse($conversationArray);
    }
}
