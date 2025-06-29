<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Messenger;

use App\Messenger\Domain\Entity\Conversation;
use App\Messenger\Domain\Entity\Message;
use App\Messenger\Domain\Entity\MessageStatus;
use App\Messenger\Domain\Enum\MessageStatusType;
use App\User\Application\Service\NotificationService;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class MessengerController
 *
 * @package App\User\Transport\Controller\Api\v1\Messenger
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Messenger')]
readonly class CreateMessengerController
{
    public function __construct(
        private NotificationService $notificationService
    )
    {
    }

    /**
     * @param User                   $loggedInUser
     * @param string                 $id
     * @param Request                $request
     * @param EntityManagerInterface $em
     *
     * @throws JsonException
     * @throws TransportExceptionInterface
     * @return JsonResponse
     */
    #[Route('/v1/messenger/conversations/{id}/messages', methods: ['POST'])]
    public function __invoke(User $loggedInUser, string $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $conversation = $em->getRepository(Conversation::class)->find($id);

        if (!$conversation) {
            return new JsonResponse(['error' => 'Conversation not found'], 404);
        }

        $sender = $em->getRepository(User::class)->find($data['sender']);
        if (!$sender) {
            return new JsonResponse(['error' => 'Sender not found'], 404);
        }

        $this->notificationService->createNotification(
            $request->headers->get('Authorization'),
            'PUSH',
            $loggedInUser,
            $data['text'],
            $conversation->getId()
        );
        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($sender);
        $message->setText($data['text'] ?? null);
        $message->setMediaUrl($data['mediaUrl'] ?? null);
        $message->setMediaType($data['mediaType'] ?? null);
        $message->setAttachmentUrl($data['attachmentUrl'] ?? null);
        $message->setAttachmentType($data['attachmentType'] ?? null);

        if (isset($data['replyTo'])) {
            $replyTo = $em->getRepository(Message::class)->find($data['replyTo']);
            if ($replyTo) {
                $message->setReplyTo($replyTo);
            }
        }

        $em->persist($message);

        // Initial status for all participants
        foreach ($conversation->getParticipants() as $participant) {
            $status = new MessageStatus();
            $status->setMessage($message);
            $status->setUser($participant);
            $status->setStatus(
                $participant === $sender ? MessageStatusType::READ : MessageStatusType::DELIVERED
            );
            $em->persist($status);
        }

        $em->flush();

        return new JsonResponse(['id' => $message->getId()]);
    }
}
