<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Messenger;

use App\Messenger\Domain\Entity\Conversation;
use App\Messenger\Domain\Entity\Message;
use App\Messenger\Domain\Entity\Reaction;
use App\Messenger\Domain\Entity\MessageStatus;
use App\Messenger\Domain\Enum\MessageStatusType;
use App\Messenger\Infrastructure\Repository\ConversationRepository;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/**
 * Class MessengerController
 *
 * @package App\User\Transport\Controller\Api\v1\Messenger
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Messenger')]
readonly class MessengerController
{
    public function __construct(
        private ConversationRepository $conversationRepository
    )
    {
    }


    /**
     * @param EntityManagerInterface $em
     * @param User                   $loggedInUser
     *
     * @throws NotSupported
     * @return JsonResponse
     */
    #[Route('/v1/messenger/conversations', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $em, User $loggedInUser): JsonResponse
    {
        $conversations = $this->conversationRepository->findAll();

        $conversationArray = [];
        foreach ($conversations as $key => $conversation) {
            foreach ($conversation->getParticipants() as $participant) {
                if(($participant->getId() === $loggedInUser->getId())) {
                    $conversationArray[$key] = $conversation;
                }
            }
        }
        return new JsonResponse(array_map(static fn(Conversation $conv) => [
            'id' => $conv->getId(),
            'title' => $conv->getTitle(),
            'isGroup' => $conv->isGroup(),
            'participants' => $conv->getParticipants()->map(fn(User $u) => [
                'id' => $u->getId(),
                'username' => $u->getUsername(),
                'firstName' => $u->getFirstName(),
                'lastName' => $u->getLastName(),
                'avatar' => $u->getProfile()?->getPhoto() ?? '/img/person.png',
            ])->toArray(),
        ], $conversationArray));
    }

    /**
     * @param Conversation           $conversation
     * @param EntityManagerInterface $em
     *
     * @return JsonResponse
     */
    #[Route('/v1/messenger/conversations/{conversation}/messages', methods: ['GET'])]
    public function fetchMessages(Conversation $conversation, EntityManagerInterface $em): JsonResponse
    {
        $messages = $em->getRepository(Message::class)->findBy([
            'conversation' => $conversation
        ]);

        $result = [];
        foreach ($messages as $key => $message) {

            $result[$key]['id'] = $message->getId();
            $result[$key]['text'] = $message->getText();
            $result[$key]['sender'] = [
                'id' => $message->getSender()?->getId(),
                'firstName' => $message->getSender()?->getFirstName(),
                'lastName' => $message->getSender()?->getLastName(),
                'avatar' => $message?->getSender()?->getProfile()?->getPhoto() ?? '/img/person.png',
             ];
            $result[$key]['mediaUrl'] = $message->getMediaUrl();
               $result[$key]['mediaType'] = $message->getMediaType();
              $result[$key]['replyTo'] = $message->getReplyTo()?->getId();
             $result[$key]['createdAt'] = $message->getCreatedAt()?->format(DATE_ATOM);
        }

        return new JsonResponse($result);
    }


    /**
     * @throws JsonException
     */
    #[Route('/v1/messenger/conversations', methods: ['POST'])]
    public function createConversation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $conversation = new Conversation();
        $conversation->setTitle($data['title'] ?? null);
        $conversation->setIsGroup($data['isGroup'] ?? false);

        foreach ($data['participants'] ?? [] as $userId) {
            $user = $em->getRepository(User::class)->find($userId);
            if ($user) {
                $conversation->getParticipants()->add($user);
            }
        }

        $em->persist($conversation);
        $em->flush();

        return new JsonResponse(['id' => $conversation->getId()]);
    }

    /**
     * @param string                 $id
     * @param Request                $request
     * @param EntityManagerInterface $em
     *
     * @throws JsonException
     * @return JsonResponse
     */
    #[Route('/v1/messenger/conversations/{id}/messages', methods: ['POST'])]
    public function sendMessage(string $id, Request $request, EntityManagerInterface $em): JsonResponse
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

    /**
     * @param string                 $id
     * @param Request                $request
     * @param EntityManagerInterface $em
     *
     * @return JsonResponse
     */
    #[Route('/v1/messenger/messages/{id}/reactions', methods: ['POST'])]
    public function addReaction(string $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $em->getRepository(Message::class)->find($id);

        if (!$message) {
            return new JsonResponse(['error' => 'Message not found'], 404);
        }

        $user = $em->getRepository(User::class)->find($data['user']);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $reaction = new Reaction();
        $reaction->setMessage($message);
        $reaction->setUser($user);
        $reaction->setEmoji($data['emoji'] ?? '');

        $em->persist($reaction);
        $em->flush();

        return new JsonResponse(['id' => $reaction->getId()]);
    }

    /**
     * @param string                 $id
     * @param Request                $request
     * @param EntityManagerInterface $em
     *
     * @return JsonResponse
     */
    #[Route('/v1/messenger/messages/{id}/status', methods: ['PATCH'])]
    public function updateMessageStatus(string $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $em->getRepository(Message::class)->find($id);

        if (!$message) {
            return new JsonResponse(['error' => 'Message not found'], 404);
        }

        $user = $em->getRepository(User::class)->find($data['user']);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $status = $em->getRepository(MessageStatus::class)->findOneBy([
            'message' => $message,
            'user' => $user,
        ]);

        if (!$status) {
            return new JsonResponse(['error' => 'Status not found'], 404);
        }

        $status->setStatus(MessageStatusType::READ);
        $em->flush();

        return new JsonResponse(['status' => 'updated']);
    }
}
