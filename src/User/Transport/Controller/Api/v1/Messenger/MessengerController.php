<?php

declare(strict_types=1);

namespace App\User\Transport\Controller\Api\v1\Messenger;

use App\Messenger\Domain\Entity\Conversation;
use App\Messenger\Domain\Entity\Message;
use App\Messenger\Domain\Entity\MessageStatus;
use App\Messenger\Domain\Entity\Reaction;
use App\Messenger\Domain\Enum\MessageStatusType;
use App\Messenger\Infrastructure\Repository\ConversationRepository;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use JsonException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @param Request                $request
     * @param EntityManagerInterface $em
     *
     * @throws JsonException
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @return JsonResponse
     */
    #[Route('/v1/messenger/conversations', methods: ['POST'])]
    public function createConversation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $users = [];
        $i = 0;
        foreach ($data['participants'] ?? [] as $userId) {
            $users[$i] = $userId;
            $i++;
        }
        $conversations = $this->conversationRepository->findAll();

        $conversationArray = [];
        foreach ($conversations as $key => $conversation) {
            foreach ($conversation->getParticipants() as $participant) {
                if(($participant->getId() === $users[0])) {
                    $conversationArray[$key]['team1'] = $conversation->getId();
                }
                if(($participant->getId() === $users[1])) {
                    $conversationArray[$key]['team2'] = $conversation->getId();
                }
            }
        }

        $oldConversation = false;
        $oldConversationId = null;

        foreach ($conversationArray as $key => $convArray) {
            if(isset($convArray[$key]['team1'], $convArray[$key]['team2']) && $convArray[$key]['team1'] === $convArray[$key]['team2']) {
                $oldConversation = true;
                $oldConversationId = $convArray[$key]['team1'];
            }
            if(isset($convArray[$key]['team2'], $convArray[$key]['team1']) && $convArray[$key]['team1'] === $convArray[$key]['team2']) {
                $oldConversation = true;
                $oldConversationId = $convArray[$key]['team1'];
            }
        }

        if($oldConversation) {
            $conversation = $this->conversationRepository->find($oldConversationId);
        } else {
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

            foreach ($data['participants'] ?? [] as $userId) {
                $user = $em->getRepository(User::class)->find($userId);
                if ($user) {
                    $message = new Message();
                    $message->setMediaUrl('test.com');
                    $message->setSender($user);
                    $message->setConversation($conversation);
                    $em->persist($message);
                    $em->flush();
                }
            }


        }

        $participants = [];
        foreach ($conversation->getParticipants() as $key => $participant) {
            $participants[$key] = [
                'id' => $participant->getId(),
                'username' => $participant->getUsername(),
                'firstName' => $participant->getFirstName(),
                'lastName' => $participant->getLastName(),
                'avatar' => $participant->getProfile()?->getPhoto() ?? 'https://bro-world-space.com/img/person.png'
            ];
        }
        return new JsonResponse([
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'isGroup' => $conversation->isGroup(),
            'participants' => $participants
        ]);
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
