<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Messenger;

use App\General\Domain\Utils\JSON;
use App\Messenger\Domain\Entity\Conversation;
use App\Messenger\Domain\Entity\Message;
use App\Messenger\Domain\Entity\Reaction;
use App\Messenger\Domain\Entity\MessageStatus;
use App\Messenger\Domain\Enum\MessageStatusType;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class MessengerController
 *
 * @package App\User\Transport\Controller\Api\v1\Messenger
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsController]
#[OA\Tag(name: 'Messenger')]
class MessengerController
{
    public function __construct(
        private readonly SerializerInterface $serializer
    )
    {
    }


    /**
     * @throws JsonException
     */
    #[Route('/v1/messenger/conversations', methods: ['GET'])]
    public function fetchConversation(EntityManagerInterface $em): JsonResponse
    {
        $conversations = $em->getRepository(Conversation::class)->findAll();

        return new JsonResponse(array_map(static fn ($conv) => [
            'id' => $conv->getId(),
            'title' => $conv->getTitle(),
            'isGroup' => $conv->isGroup(),
            'participants' => array_map(static fn ($user) => [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'avatar' => $user->getProfile()?->getPhoto() ?? '/img/person.png',
            ], $conv->getParticipants()->toArray()),
        ], $conversations));
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
                 'id' => $message->getSender()->getId(),
                 'username' => $message->getSender()->getUsername()
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
