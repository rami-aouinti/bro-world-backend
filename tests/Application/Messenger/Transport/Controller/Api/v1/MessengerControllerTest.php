<?php

declare(strict_types=1);

namespace App\Tests\Application\Messenger\Transport\Controller\Api\v1;

use App\General\Domain\Utils\JSON;
use App\Messenger\Application\DTO\Conversation\Conversation as ConversationDto;
use App\Messenger\Application\DTO\Message\Message as MessageDto;
use App\Messenger\Application\DTO\MessageStatus\MessageStatus as MessageStatusDto;
use App\Messenger\Application\Resource\ConversationResource;
use App\Messenger\Application\Resource\MessageResource;
use App\Messenger\Application\Resource\MessageStatusResource;
use App\Messenger\Domain\Entity\Conversation;
use App\Messenger\Domain\Entity\Message as MessageEntity;
use App\Messenger\Domain\Entity\MessageStatus as MessageStatusEntity;
use App\Messenger\Domain\Repository\Interfaces\ConversationDocumentRepositoryInterface;
use App\Messenger\Domain\Repository\Interfaces\MessageDocumentRepositoryInterface;
use App\Messenger\Domain\Repository\Interfaces\MessageStatusDocumentRepositoryInterface;
use App\Messenger\Domain\Repository\Interfaces\ReactionDocumentRepositoryInterface;
use App\Messenger\Domain\Enum\MessageStatusType;
use App\Tests\TestCase\WebTestCase;
use App\User\Application\Resource\UserResource;
use App\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @package App\Tests
 */
class MessengerControllerTest extends WebTestCase
{
    private ConversationResource $conversationResource;
    private MessageResource $messageResource;
    private MessageStatusResource $messageStatusResource;
    private UserResource $userResource;
    private ConversationDocumentRepositoryInterface $conversationDocumentRepository;
    private MessageDocumentRepositoryInterface $messageDocumentRepository;
    private MessageStatusDocumentRepositoryInterface $messageStatusDocumentRepository;
    private ReactionDocumentRepositoryInterface $reactionDocumentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $container = static::getContainer();
        $this->conversationResource = $container->get(ConversationResource::class);
        $this->messageResource = $container->get(MessageResource::class);
        $this->messageStatusResource = $container->get(MessageStatusResource::class);
        $this->userResource = $container->get(UserResource::class);
        $this->conversationDocumentRepository = $container->get(ConversationDocumentRepositoryInterface::class);
        $this->messageDocumentRepository = $container->get(MessageDocumentRepositoryInterface::class);
        $this->messageStatusDocumentRepository = $container->get(MessageStatusDocumentRepositoryInterface::class);
        $this->reactionDocumentRepository = $container->get(ReactionDocumentRepositoryInterface::class);
    }

    /**
     * @throws Throwable
     */
    public function testConversationListRequiresAuthentication(): void
    {
        $client = $this->getTestClient();

        $client->request('GET', self::API_URL_PREFIX . '/v1/messenger/conversation');
        $response = $client->getResponse();
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @throws Throwable
     */
    public function testConversationMyRouteReturnsConversations(): void
    {
        $user = $this->getUser('john-user');
        $admin = $this->getUser('john-admin');
        $conversation = $this->createConversationForUsers([$user, $admin], 'Listable conversation');

        $client = $this->getTestClient('john-user', 'password-user');
        $client->request('GET', self::API_URL_PREFIX . '/v1/messenger/conversation/my');

        $response = $client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        self::assertNotFalse($content);
        $data = JSON::decode($content, true);
        self::assertIsArray($data);

        $ids = array_map(static fn (array $item): string => $item['id'], $data);
        self::assertContains($conversation->getId(), $ids);
    }

    /**
     * @throws Throwable
     */
    public function testConversationCreate(): void
    {
        $user = $this->getUser('john-user');
        $admin = $this->getUser('john-admin');

        $client = $this->getTestClient('john-user', 'password-user');
        $payload = [
            'title' => 'API conversation',
            'isGroup' => false,
            'participants' => [
                $user->getId(),
                $admin->getId(),
            ],
        ];

        $client->request(
            method: 'POST',
            uri: self::API_URL_PREFIX . '/v1/messenger/conversation',
            server: $this->getJsonHeaders(),
            content: JSON::encode($payload),
        );

        $response = $client->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $content = $response->getContent();
        self::assertNotFalse($content);
        $data = JSON::decode($content, true);
        self::assertIsArray($data);
        self::assertArrayHasKey('title', $data);
        self::assertSame('API conversation', $data['title']);
    }

    /**
     * @throws Throwable
     */
    public function testConversationCreateDirect(): void
    {
        $user = $this->getUser('john-user');
        $admin = $this->getUser('john-admin');

        $client = $this->getTestClient('john-user', 'password-user');
        $payload = [
            'receiverId' => $admin->getId(),
        ];

        $client->request(
            method: 'POST',
            uri: self::API_URL_PREFIX . '/v1/messenger/conversation/direct',
            server: $this->getJsonHeaders(),
            content: JSON::encode($payload),
        );

        $response = $client->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $content = $response->getContent();
        self::assertNotFalse($content);
        $data = JSON::decode($content, true);
        self::assertIsArray($data);
        self::assertArrayHasKey('participants', $data);
        self::assertCount(2, $data['participants']);

        $participantIds = array_map(
            static fn (array $participant): ?string => $participant['id'] ?? null,
            $data['participants'],
        );

        self::assertContains($user->getId(), $participantIds);
        self::assertContains($admin->getId(), $participantIds);
    }

    /**
     * @throws Throwable
     */
    public function testMessageCreateAndList(): void
    {
        $user = $this->getUser('john-user');
        $admin = $this->getUser('john-admin');
        $conversation = $this->createConversationForUsers([$user, $admin], 'Message conversation');

        $client = $this->getTestClient('john-user', 'password-user');
        $payload = [
            'conversation' => $conversation->getId(),
            'sender' => $user->getId(),
            'text' => 'Hello from tests',
        ];

        $client->request(
            method: 'POST',
            uri: self::API_URL_PREFIX . '/v1/messenger/message',
            server: $this->getJsonHeaders(),
            content: JSON::encode($payload),
        );

        $response = $client->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        $client->request(
            method: 'GET',
            uri: self::API_URL_PREFIX . '/v1/messenger/message/conversation/' . $conversation->getId(),
        );

        $listResponse = $client->getResponse();
        self::assertSame(Response::HTTP_OK, $listResponse->getStatusCode());
        $content = $listResponse->getContent();
        self::assertNotFalse($content);
        $data = JSON::decode($content, true);
        self::assertIsArray($data);
        $texts = array_map(static fn (array $item): ?string => $item['text'] ?? null, $data);
        self::assertContains('Hello from tests', $texts);
    }

    public function testMessageListRequiresParticipation(): void
    {
        $user = $this->getUser('john-user');
        $admin = $this->getUser('john-admin');
        $conversation = $this->createConversationForUsers([$user, $admin], 'Restricted conversation');

        $client = $this->getTestClient('john-logged', 'password-logged');
        $client->request(
            method: 'GET',
            uri: self::API_URL_PREFIX . '/v1/messenger/message/conversation/' . $conversation->getId(),
        );

        $response = $client->getResponse();
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * @throws Throwable
     */
    public function testReactionCreate(): void
    {
        $user = $this->getUser('john-user');
        $admin = $this->getUser('john-admin');
        $conversation = $this->createConversationForUsers([$user, $admin], 'Reaction conversation');
        $message = $this->createMessageForConversation($conversation, $user, 'Message for reaction');

        $client = $this->getTestClient('john-user', 'password-user');
        $payload = [
            'message' => $message->getId(),
            'user' => $admin->getId(),
            'emoji' => '👍',
        ];

        $client->request(
            method: 'POST',
            uri: self::API_URL_PREFIX . '/v1/messenger/reaction',
            server: $this->getJsonHeaders(),
            content: JSON::encode($payload),
        );

        $response = $client->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $content = $response->getContent();
        self::assertNotFalse($content);
        $data = JSON::decode($content, true);
        self::assertIsArray($data);
        self::assertSame('👍', $data['emoji'] ?? null);
        self::assertArrayHasKey('id', $data);
        $this->assertReactionDocumentExists((string) $data['id']);
    }

    /**
     * @throws Throwable
     */
    public function testMessageStatusPatch(): void
    {
        $user = $this->getUser('john-user');
        $admin = $this->getUser('john-admin');
        $conversation = $this->createConversationForUsers([$user, $admin], 'Status conversation');
        $message = $this->createMessageForConversation($conversation, $user, 'Message needing status');
        $status = $this->createMessageStatusForMessage($message, $admin);

        $client = $this->getTestClient('john-user', 'password-user');
        $payload = [
            'status' => MessageStatusType::READ->value,
        ];

        $client->request(
            method: 'PATCH',
            uri: self::API_URL_PREFIX . '/v1/messenger/message_status/' . $status->getId(),
            server: $this->getJsonHeaders(),
            content: JSON::encode($payload),
        );

        $response = $client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        self::assertNotFalse($content);
        $data = JSON::decode($content, true);
        self::assertIsArray($data);
        self::assertSame(MessageStatusType::READ->value, $data['status'] ?? null);
    }

    /**
     * @return array<int, string>
     */
    public function getJsonHeaders(): array
    {
        return parent::getJsonHeaders();
    }

    private function getUser(string $username): User
    {
        $user = $this->userResource->findOneBy([
            'username' => $username,
        ]);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    /**
     * @param array<int, User> $users
     */
    private function createConversationForUsers(array $users, string $title): Conversation
    {
        $dto = (new ConversationDto())
            ->setTitle($title)
            ->setIsGroup(false)
            ->setParticipants($users);

        $conversation = $this->conversationResource->create($dto);
        self::assertInstanceOf(Conversation::class, $conversation);
        self::assertNotNull($this->conversationDocumentRepository->find($conversation->getId()));

        return $conversation;
    }

    private function createMessageForConversation(Conversation $conversation, User $sender, string $text): MessageEntity
    {
        $dto = (new MessageDto())
            ->setConversation($conversation)
            ->setSender($sender)
            ->setText($text);

        $message = $this->messageResource->create($dto);
        self::assertInstanceOf(MessageEntity::class, $message);
        self::assertNotNull($this->messageDocumentRepository->find($message->getId()));

        return $message;
    }

    private function createMessageStatusForMessage(MessageEntity $message, User $user): MessageStatusEntity
    {
        $dto = (new MessageStatusDto())
            ->setMessage($message)
            ->setUser($user)
            ->setStatus(MessageStatusType::DELIVERED);

        $status = $this->messageStatusResource->create($dto);
        self::assertInstanceOf(MessageStatusEntity::class, $status);
        self::assertNotNull($this->messageStatusDocumentRepository->find($status->getId()));

        return $status;
    }

    private function assertReactionDocumentExists(string $reactionId): void
    {
        self::assertNotNull($this->reactionDocumentRepository->find($reactionId));
    }
}
